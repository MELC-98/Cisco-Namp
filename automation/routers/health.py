"""
Health check router.
"""
from fastapi import APIRouter

router = APIRouter()


@router.get("/health")
async def health_check():
    """Health check endpoint — exempted from API key authentication."""
    return {
        "status": "healthy",
        "service": "cisco-namp-automation",
        "version": "1.0.0",
    }
