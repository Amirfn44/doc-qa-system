import time
from functools import wraps
from typing import Callable, Any
import logging
from threading import Lock

logger = logging.getLogger(__name__)


class RateLimitException(Exception):
    """Exception raised when rate limit is exceeded."""
    pass


class LLMRateLimiter:
    """
    Rate limiter for LLM API calls with exponential backoff.
    Handles rate limits gracefully with automatic retries.
    """

    def __init__(
        self,
        max_requests_per_minute: int = 60,
        max_retries: int = 5,
        initial_retry_delay: float = 1.0,
        max_retry_delay: float = 60.0,
        backoff_factor: float = 2.0
    ):
        """
        Initialize rate limiter.

        Args:
            max_requests_per_minute: Maximum API calls per minute
            max_retries: Maximum number of retry attempts
            initial_retry_delay: Initial delay between retries (seconds)
            max_retry_delay: Maximum delay between retries (seconds)
            backoff_factor: Multiplier for exponential backoff
        """
        self.max_requests_per_minute = max_requests_per_minute
        self.max_retries = max_retries
        self.initial_retry_delay = initial_retry_delay
        self.max_retry_delay = max_retry_delay
        self.backoff_factor = backoff_factor

        self.request_times = []
        self.lock = Lock()

    def wait_if_needed(self):
        """
        Wait if necessary to respect rate limits.
        """
        with self.lock:
            current_time = time.time()

            self.request_times = [
                t for t in self.request_times
                if current_time - t < 60
            ]

            if len(self.request_times) >= self.max_requests_per_minute:
                oldest_request = min(self.request_times)
                wait_time = 60 - (current_time - oldest_request)

                if wait_time > 0:
                    logger.warning(
                        f"Rate limit reached. Waiting {wait_time:.2f} seconds..."
                    )
                    time.sleep(wait_time)
                    current_time = time.time()

            self.request_times.append(current_time)

    def call_with_retry(
        self,
        func: Callable,
        *args,
        **kwargs
    ) -> Any:
        """
        Call function with automatic retry on rate limit errors.

        Args:
            func: Function to call
            *args: Positional arguments
            **kwargs: Keyword arguments

        Returns:
            Function result

        Raises:
            RateLimitException: If max retries exceeded
        """
        retry_delay = self.initial_retry_delay

        for attempt in range(self.max_retries):
            try:
                self.wait_if_needed()

                result = func(*args, **kwargs)

                if attempt > 0:
                    logger.info(f"Request succeeded after {attempt} retries")

                return result

            except Exception as e:
                error_message = str(e).lower()

                is_rate_limit = any(
                    keyword in error_message
                    for keyword in [
                        'rate limit',
                        'too many requests',
                        '429',
                        'quota exceeded',
                        'throttled'
                    ]
                )

                if is_rate_limit:
                    if attempt < self.max_retries - 1:
                        logger.warning(
                            f"Rate limit hit (attempt {attempt + 1}/{self.max_retries}). "
                            f"Retrying in {retry_delay:.2f} seconds..."
                        )
                        time.sleep(retry_delay)

                        retry_delay = min(
                            retry_delay * self.backoff_factor,
                            self.max_retry_delay
                        )
                    else:
                        logger.error("Max retries exceeded for rate limit")
                        raise RateLimitException(
                            f"Rate limit exceeded after {self.max_retries} attempts"
                        ) from e
                else:
                    raise

        raise RateLimitException("Unexpected: max retries reached without success")


def rate_limited(
    max_requests_per_minute: int = 60,
    max_retries: int = 5
):
    """
    Decorator for rate limiting LLM calls.

    Usage:
        @rate_limited(max_requests_per_minute=20)
        def my_llm_call():
            return llm.invoke("query")

    Args:
        max_requests_per_minute: Maximum API calls per minute
        max_retries: Maximum retry attempts
    """
    limiter = LLMRateLimiter(
        max_requests_per_minute=max_requests_per_minute,
        max_retries=max_retries
    )

    def decorator(func: Callable) -> Callable:
        @wraps(func)
        def wrapper(*args, **kwargs):
            return limiter.call_with_retry(func, *args, **kwargs)
        return wrapper
    return decorator


OLLAMA_LIMITER = LLMRateLimiter(max_requests_per_minute=100)
COHERE_LIMITER = LLMRateLimiter(max_requests_per_minute=20)
OPENAI_LIMITER = LLMRateLimiter(max_requests_per_minute=50)
