"""
Cisco NAMP — Netmiko Service
Core SSH service for Cisco device communication.
All device interactions go through this service.
"""
import time
import logging
from typing import Optional, Dict, Any, List

from netmiko import ConnectHandler, NetmikoTimeoutException, NetmikoAuthenticationException
from netmiko.exceptions import NetmikoBaseException

from config import settings
from services.parsers.show_version import parse_show_version
from services.parsers.show_interfaces import parse_show_ip_interface_brief
from services.parsers.show_inventory import parse_show_inventory

logger = logging.getLogger(__name__)


def _build_device_params(host: str, port: int, username: str, password: str,
                          device_type: str, enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """Build Netmiko connection parameters. Passwords are never logged."""
    params = {
        "device_type": device_type,
        "host": host,
        "port": port,
        "username": username,
        "password": password,
        "timeout": settings.ssh_timeout,
        "conn_timeout": settings.ssh_conn_timeout,
        "fast_cli": False,
    }
    if enable_secret:
        params["secret"] = enable_secret
    return params


def connect_device(host: str, port: int, username: str, password: str,
                   device_type: str, enable_secret: Optional[str] = None) -> ConnectHandler:
    """
    Establish SSH connection to a Cisco device.
    Raises descriptive exceptions on failure.
    """
    params = _build_device_params(host, port, username, password, device_type, enable_secret)

    try:
        connection = ConnectHandler(**params)
        if enable_secret:
            connection.enable()
        logger.info(f"Connected to {host}:{port}")
        return connection
    except NetmikoAuthenticationException:
        raise ConnectionError(f"Authentication failed for {host}:{port}")
    except NetmikoTimeoutException:
        raise ConnectionError(f"Connection timed out for {host}:{port}")
    except Exception as e:
        raise ConnectionError(f"Failed to connect to {host}:{port}: {str(e)}")


def disconnect_device(connection: ConnectHandler) -> None:
    """Safely close an SSH connection."""
    try:
        connection.disconnect()
    except Exception:
        pass


def test_connection(host: str, port: int, username: str, password: str,
                    device_type: str, enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Test SSH connectivity to a device.
    Returns success status and response time.
    """
    start_time = time.time()

    try:
        conn = connect_device(host, port, username, password, device_type, enable_secret)
        # Send a simple command to verify the connection
        conn.find_prompt()
        disconnect_device(conn)

        elapsed_ms = round((time.time() - start_time) * 1000, 2)

        return {
            "success": True,
            "message": "Connection successful",
            "response_time_ms": elapsed_ms,
        }
    except ConnectionError as e:
        return {
            "success": False,
            "message": str(e),
            "response_time_ms": None,
        }


def get_device_facts(host: str, port: int, username: str, password: str,
                     device_type: str, enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Discover device facts using show version and show inventory.
    """
    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        # Get show version output
        version_output = conn.send_command("show version", read_timeout=30)
        facts = parse_show_version(version_output)

        # Get show inventory output
        try:
            inventory_output = conn.send_command("show inventory", read_timeout=30)
            inventory_facts = parse_show_inventory(inventory_output)
            if inventory_facts.get("serial_number"):
                facts["serial_number"] = inventory_facts["serial_number"]
            if inventory_facts.get("model"):
                facts["model"] = facts.get("model") or inventory_facts["model"]
        except Exception:
            pass  # Inventory is optional

        return facts

    finally:
        disconnect_device(conn)


def get_interfaces(host: str, port: int, username: str, password: str,
                   device_type: str, enable_secret: Optional[str] = None) -> List[Dict[str, Any]]:
    """
    Retrieve interface information from a device.
    """
    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        output = conn.send_command("show ip interface brief", read_timeout=30)
        interfaces = parse_show_ip_interface_brief(output)

        # Try to get detailed interface info
        try:
            detail_output = conn.send_command("show interfaces", read_timeout=60)
            # Enrich basic info with details from show interfaces
            _enrich_interfaces(interfaces, detail_output)
        except Exception:
            pass

        return interfaces

    finally:
        disconnect_device(conn)


def run_show_command(host: str, port: int, username: str, password: str,
                     device_type: str, command: str,
                     enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Execute an approved show command and return the output.
    """
    # Defense in depth: block config commands even if they get past Laravel validation
    blocked_prefixes = ["configure", "conf t", "write", "copy", "delete", "erase", "reload"]
    cmd_lower = command.strip().lower()
    for blocked in blocked_prefixes:
        if cmd_lower.startswith(blocked):
            return {
                "command": command,
                "output": "",
                "success": False,
                "error": "Configuration commands are blocked",
            }

    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        output = conn.send_command(command, read_timeout=60)

        return {
            "command": command,
            "output": output,
            "success": True,
        }
    except Exception as e:
        return {
            "command": command,
            "output": "",
            "success": False,
            "error": str(e),
        }
    finally:
        disconnect_device(conn)


def backup_running_config(host: str, port: int, username: str, password: str,
                          device_type: str,
                          enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Retrieve the running-config from a device.
    """
    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        config = conn.send_command("show running-config", read_timeout=60)

        return {
            "config": config,
            "success": True,
            "size": len(config),
        }
    except Exception as e:
        return {
            "config": "",
            "success": False,
            "size": 0,
            "error": str(e),
        }
    finally:
        disconnect_device(conn)


def configure_interface(host: str, port: int, username: str, password: str,
                        device_type: str, config_lines: List[str],
                        enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Apply configuration lines to a device.
    Uses Netmiko's send_config_set for safe configuration delivery.
    """
    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        # Apply configuration
        output = conn.send_config_set(config_lines)

        # Save configuration
        save_output = conn.save_config()

        # Verify by checking the interface
        # Extract interface name from config_lines
        interface_name = None
        for line in config_lines:
            if line.strip().lower().startswith("interface "):
                interface_name = line.strip()[len("interface "):]
                break

        verification = ""
        if interface_name:
            verification = conn.send_command(
                f"show running-config interface {interface_name}",
                read_timeout=30,
            )

        return {
            "success": True,
            "message": "Configuration applied successfully",
            "output": output,
            "verification": verification,
        }
    except Exception as e:
        return {
            "success": False,
            "message": f"Configuration failed: {str(e)}",
            "output": "",
            "verification": "",
        }
    finally:
        disconnect_device(conn)


def deploy_raw_config(host: str, port: int, username: str, password: str,
                        device_type: str, config_lines: List[str],
                        enable_secret: Optional[str] = None) -> Dict[str, Any]:
    """
    Apply raw configuration lines to a device.
    Uses Netmiko's send_config_set for safe configuration delivery.
    """
    conn = connect_device(host, port, username, password, device_type, enable_secret)

    try:
        # Apply configuration
        output = conn.send_config_set(config_lines)

        # Save configuration
        save_output = conn.save_config()

        return {
            "success": True,
            "message": "Raw configuration applied successfully",
            "output": output,
            "verification": save_output,
        }
    except Exception as e:
        return {
            "success": False,
            "message": f"Configuration failed: {str(e)}",
            "output": "",
            "verification": "",
        }
    finally:
        disconnect_device(conn)


def _enrich_interfaces(interfaces: List[Dict], detail_output: str) -> None:
    """Enrich basic interface data with details from show interfaces."""
    # Simple parsing of show interfaces output
    current_iface = None
    for line in detail_output.splitlines():
        # Match interface header line (e.g., "GigabitEthernet0/0 is up, line protocol is up")
        if not line.startswith(" ") and " is " in line:
            iface_name = line.split(" is ")[0].strip()
            current_iface = None
            for iface in interfaces:
                if iface["name"] == iface_name:
                    current_iface = iface

                    # Parse admin/oper status from the line
                    parts = line.lower()
                    if "administratively down" in parts:
                        iface["admin_status"] = "down"
                    elif " is up" in parts:
                        iface["admin_status"] = "up"

                    if "line protocol is up" in parts:
                        iface["oper_status"] = "up"
                    elif "line protocol is down" in parts:
                        iface["oper_status"] = "down"
                    break

        elif current_iface and "  Hardware is " in line:
            # Extract MAC address
            if "address is " in line:
                mac = line.split("address is ")[-1].split(" ")[0].strip()
                current_iface["mac_address"] = mac

        elif current_iface and "  Description:" in line:
            current_iface["description"] = line.split("Description:")[-1].strip()

        elif current_iface and ("  BW " in line or "  MTU " in line):
            if "BW " in line:
                try:
                    bw = line.split("BW ")[1].split(" ")[0]
                    current_iface["speed"] = bw
                except (IndexError, ValueError):
                    pass

        elif current_iface and ("  Full-duplex" in line or "  Half-duplex" in line or "  Auto-duplex" in line):
            if "Full-duplex" in line:
                current_iface["duplex"] = "full"
            elif "Half-duplex" in line:
                current_iface["duplex"] = "half"
            elif "Auto-duplex" in line:
                current_iface["duplex"] = "auto"
