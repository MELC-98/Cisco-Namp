"""
Cisco NAMP — FastAPI Automation Service Configuration
"""
import os
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """Application settings loaded from environment variables."""

    # API Security
    api_key: str = os.getenv("AUTOMATION_API_KEY", "change_me_automation_api_key")

    # Service
    app_name: str = "Cisco NAMP Automation Service"
    debug: bool = os.getenv("APP_DEBUG", "true").lower() == "true"

    # SSH Defaults
    ssh_timeout: int = 30
    ssh_conn_timeout: int = 15

    class Config:
        env_file = ".env"


settings = Settings()
