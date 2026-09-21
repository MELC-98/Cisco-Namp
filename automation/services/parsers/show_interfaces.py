"""
Parser for Cisco 'show ip interface brief' output.
"""
import re
from typing import List, Dict, Any


def parse_show_ip_interface_brief(output: str) -> List[Dict[str, Any]]:
    """
    Parse show ip interface brief output.
    
    Example input:
    Interface              IP-Address      OK? Method Status                Protocol
    GigabitEthernet0/0     10.10.1.1       YES manual up                    up
    GigabitEthernet0/1     unassigned      YES unset  administratively down down
    """
    interfaces = []

    if not output:
        return interfaces

    lines = output.strip().splitlines()

    # Skip header line(s)
    data_started = False
    for line in lines:
        if "Interface" in line and "IP-Address" in line:
            data_started = True
            continue

        if not data_started:
            continue

        if not line.strip():
            continue

        # Parse the interface line
        parts = line.split()
        if len(parts) >= 6:
            name = parts[0]
            ip_address = parts[1] if parts[1] != "unassigned" else None
            status = parts[4] if len(parts) >= 5 else "down"
            protocol = parts[5] if len(parts) >= 6 else "down"

            # Handle "administratively down" which splits into multiple parts
            if "administratively" in line.lower():
                admin_status = "down"
                oper_status = "down"
            else:
                admin_status = "up" if status.lower() == "up" else "down"
                oper_status = "up" if protocol.lower() == "up" else "down"

            # Determine interface type
            iface_type = _determine_interface_type(name)

            interfaces.append({
                "name": name,
                "ip_address": ip_address,
                "subnet_mask": None,
                "mac_address": None,
                "speed": None,
                "duplex": None,
                "description": None,
                "admin_status": admin_status,
                "oper_status": oper_status,
                "type": iface_type,
            })

    return interfaces


def _determine_interface_type(name: str) -> str:
    """Determine interface type from its name."""
    name_lower = name.lower()
    if name_lower.startswith("gi") or name_lower.startswith("fa") or name_lower.startswith("te") or name_lower.startswith("eth"):
        return "ethernet"
    elif name_lower.startswith("lo"):
        return "loopback"
    elif name_lower.startswith("vlan"):
        return "vlan"
    elif name_lower.startswith("tu"):
        return "tunnel"
    elif name_lower.startswith("se"):
        return "serial"
    elif name_lower.startswith("po"):
        return "port-channel"
    else:
        return "other"
