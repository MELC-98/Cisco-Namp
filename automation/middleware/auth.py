"""
API Key authentication middleware.
Validates the X-API-Key header on all requests except /health and /docs.
"""
from fastapi import Request, HTTPException
from starlette.middleware.base import BaseHTTPMiddleware

from config import settings


class APIKeyMiddleware(BaseHTTPMiddleware):
    """Validate API key on incoming requests from Laravel."""

    EXEMPT_PATHS = {"/health", "/docs", "/openapi.json"}

    async def dispatch(self, request: Request, call_next):
        if request.url.path in self.EXEMPT_PATHS:
            return await call_next(request)

        api_key = request.headers.get("X-API-Key")

        if not api_key or api_key != settings.api_key:
            raise HTTPException(
                status_code=401,
                detail="Invalid or missing API key",
            )

        return await call_next(request)
