"""
Pydantic models for API request/response schemas.
"""
from typing import Optional, List
from pydantic import BaseModel


class DeviceConnection(BaseModel):
    """SSH connection parameters received from Laravel."""
    host: str
    port: int = 22
    username: str
    password: str
    device_type: str = "cisco_ios"
    enable_secret: Optional[str] = None


class CommandRequest(DeviceConnection):
    """Request to execute a show command."""
    command: str


class InterfaceConfigRequest(DeviceConnection):
    """Request to configure an interface."""
    config_lines: List[str]


class RawConfigRequest(DeviceConnection):
    """Request to deploy raw configuration lines."""
    config_lines: List[str]


class ConnectionResult(BaseModel):
    """Result of a connection test."""
    success: bool
    message: str
    response_time_ms: Optional[float] = None


class DeviceFacts(BaseModel):
    """Discovered device facts."""
    hostname: Optional[str] = None
    model: Optional[str] = None
    serial_number: Optional[str] = None
    os_name: Optional[str] = None
    os_version: Optional[str] = None
    uptime: Optional[str] = None
    hardware: Optional[str] = None
    image_file: Optional[str] = None


class CommandOutput(BaseModel):
    """Output from a show command."""
    command: str
    output: str
    success: bool


class BackupResult(BaseModel):
    """Result of a configuration backup."""
    config: str
    success: bool
    size: int


class InterfaceInfo(BaseModel):
    """Interface information."""
    name: str
    description: Optional[str] = None
    ip_address: Optional[str] = None
    subnet_mask: Optional[str] = None
    mac_address: Optional[str] = None
    speed: Optional[str] = None
    duplex: Optional[str] = None
    admin_status: str = "down"
    oper_status: str = "down"
    type: Optional[str] = None


class ConfigResult(BaseModel):
    """Result of a configuration deployment."""
    success: bool
    message: str
    verification: Optional[str] = None
