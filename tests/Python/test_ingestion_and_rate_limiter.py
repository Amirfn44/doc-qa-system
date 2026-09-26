import unittest
from unittest.mock import patch

from src.utils.rate_limiter import LLMRateLimiter


class RateLimiterTests(unittest.TestCase):
    def test_rate_limiter_retries_rate_limit_errors(self):
        limiter = LLMRateLimiter(
            max_requests_per_minute=100,
            max_retries=2,
            initial_retry_delay=0,
        )
        calls = 0

        def call():
            nonlocal calls
            calls += 1
            if calls == 1:
                raise RuntimeError("429 too many requests")
            return "ok"

        with patch("src.utils.rate_limiter.time.sleep"):
            self.assertEqual(limiter.call_with_retry(call), "ok")
        self.assertEqual(calls, 2)


if __name__ == "__main__":
    unittest.main()
