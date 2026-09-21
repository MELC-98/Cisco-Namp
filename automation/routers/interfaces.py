"""
Interface automation endpoints.
Handles interface discovery and configuration.
"""
from fastapi import APIRouter, HTTPException
from models.schemas import DeviceConnection, InterfaceConfigRequest

from services.netmiko_service import (
    get_interfaces,
    configure_interface,
)

router = APIRouter()


@router.post("/interfaces")
async def get_device_interfaces(device: DeviceConnection):
    """Get interface information from a device."""
    try:
        interfaces = get_interfaces(
            host=device.host,
            port=device.port,
            username=device.username,
            password=device.password,
            device_type=device.device_type,
            enable_secret=device.enable_secret,
        )
        return {"interfaces": interfaces}
    except ConnectionError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/configure-interface")
async def configure_device_interface(request: InterfaceConfigRequest):
    """Apply interface configuration to a device."""
    try:
        result = configure_interface(
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
