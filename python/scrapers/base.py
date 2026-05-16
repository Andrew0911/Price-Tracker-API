import os
import datetime
from curl_cffi import requests
from bs4 import BeautifulSoup
from typing import Dict, Optional

class BaseScraper:
    def __init__(self):
        # By default, NO store will use a proxy
        self.requires_proxy = False
        
        # Advanced headers to mimic a real browser
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
            "Accept-Language": "ro-RO,ro;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8"
        }

    def _get_proxies(self) -> Optional[Dict[str, str]]:
        # 1. If the current scraper DOES NOT have proxy enabled (default), exit directly
        if not self.requires_proxy:
            return None

        # 2. If the proxy was explicitly enabled in the child class, check the environment
        proxy_env = os.getenv("PROXY_ENV", "development")
        proxy_url = os.getenv("PROXY_URL")

        if proxy_env == "production" and proxy_url:
            return {
                "http": proxy_url,
                "https": proxy_url
            }
        
        return None

    def _log_proxy_call(self, url: str) -> None:
        #Append a short log entry (UTC datetime + URL) when a proxy is used.
        try:
            logs_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "logs"))
            os.makedirs(logs_dir, exist_ok=True)
            log_file = os.path.join(logs_dir, "proxy_calls.log")
            ts = datetime.datetime.utcnow().isoformat()
            with open(log_file, "a", encoding="utf-8") as f:
                f.write(f"{ts} {url}\n")
        except Exception:
            # Do not raise logging errors during scraping
            pass

    def get_html(self, url: str) -> BeautifulSoup:
        #Downloads the page and returns the BeautifulSoup object in a single attempt.
        proxies = self._get_proxies()
        if proxies:
            # best-effort log when routing via proxy
            self._log_proxy_call(url)
        
        # If routing through proxy, set a higher timeout due to potential latency (30s)
        timeout_val = 30 if proxies else 10
        
        # Perform the request. Any timeout or connection error will raise an exception immediately.
        response = requests.get(
            url, 
            headers=self.headers, 
            proxies=proxies, 
            timeout=timeout_val,
            impersonate="chrome124" # Added: This handles the TLS fingerprinting to bypass Cloudflare
        )
        
        # A real 404 from the store means the product page no longer exists.
        # Surface that as a user-facing "not found" condition instead of a
        # generic scraper failure.
        if response.status_code == 404:
            raise ValueError("Product not found.")

        # Raise an HTTP error if the status is not 200 (e.g., 500, 511)
        if response.status_code != 200:
            raise Exception(f"HTTP Error {response.status_code} while trying to fetch {url}")
        
        return BeautifulSoup(response.text, "html.parser")

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        # Each specific scraper (eMAG, Altex, etc.) must override this method.
        raise NotImplementedError("This method must be implemented in the child class.")

    def scrape(self, url: str) -> Dict[str, Optional[str]]:
        # Main method called by FastAPI.
        soup = self.get_html(url)
        return self.extract_product_data(soup)
