"""
Cisco NAMP — FastAPI Automation Service
Main application entry point.
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from config import settings
from routers import health, devices, interfaces
from middleware.auth import APIKeyMiddleware

app = FastAPI(
    title=settings.app_name,
    description="Internal automation service for Cisco network device management via SSH/Netmiko.",
    version="1.0.0",
    docs_url="/docs" if settings.debug else None,
    redoc_url=None,
)

# ── Middleware ────────────────────────────────────────────────

# API Key authentication for all requests
app.add_middleware(APIKeyMiddleware)

# CORS — internal only, but needed for development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"] if settings.debug else [],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Routers ──────────────────────────────────────────────────

app.include_router(health.router, tags=["Health"])
app.include_router(devices.router, prefix="/devices", tags=["Devices"])
app.include_router(interfaces.router, prefix="/devices", tags=["Interfaces"])
