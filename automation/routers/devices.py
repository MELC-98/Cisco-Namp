"""
Device automation endpoints.
Handles connection testing, discovery, command execution, and backups.
"""
from fastapi import APIRouter, HTTPException
from models.schemas import DeviceConnection, CommandRequest, RawConfigRequest

from services.netmiko_service import (
    test_connection,
    get_device_facts,
    run_show_command,
    backup_running_config,
    deploy_raw_config,
)

router = APIRouter()


@router.post("/test")
async def test_device_connection(device: DeviceConnection):
    """Test SSH connectivity to a Cisco device."""
    try:
        result = test_connection(
            host=device.host,
            port=device.port,
            username=device.username,
            password=device.password,
            device_type=device.device_type,
            enable_secret=device.enable_secret,
        )
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/discover")
async def discover_device(device: DeviceConnection):
    """Discover device facts (show version, show inventory)."""
    try:
        facts = get_device_facts(
            host=device.host,
            port=device.port,
            username=device.username,
            password=device.password,
            device_type=device.device_type,
            enable_secret=device.enable_secret,
        )
        return facts
    except ConnectionError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/command")
async def execute_command(request: CommandRequest):
    """Execute an approved show command on a device."""
    try:
        result = run_show_command(
            host=request.host,
            port=request.port,
            username=request.username,
            password=request.password,
            device_type=request.device_type,
            command=request.command,
            enable_secret=request.enable_secret,
        )
        return result
    except ConnectionError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/backup")
async def backup_config(device: DeviceConnection):
    """Backup running-config from a device."""
    try:
        result = backup_running_config(
            host=device.host,
            port=device.port,
            username=device.username,
            password=device.password,
            device_type=device.device_type,
            enable_secret=device.enable_secret,
        )
        return result
    except ConnectionError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/configure-raw")
async def configure_raw(request: RawConfigRequest):
    """Deploy raw configuration commands to a device."""
    try:
        result = deploy_raw_config(
            host=request.host,
            port=request.port,
            username=request.username,
            password=request.password,
            device_type=request.device_type,
            config_lines=request.config_lines,
            enable_secret=request.enable_secret,
        )
        return result
    except ConnectionError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
