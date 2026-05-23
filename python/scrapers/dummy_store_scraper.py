from bs4 import BeautifulSoup
from typing import Dict, Optional
from urllib.parse import urlparse, urlunparse
from curl_cffi import requests
from .base import BaseScraper


class DummyStoreScraper(BaseScraper):
    def get_html(self, url: str) -> BeautifulSoup:
        response = requests.get(
            url,
            headers=self.headers,
            timeout=10,
        )

        if response.status_code != 200:
            raise Exception(f"HTTP Error {response.status_code} while trying to fetch {url}")

        return BeautifulSoup(response.text, "html.parser")

    def scrape(self, url: str) -> Dict[str, Optional[str]]:
        try:
            return super().scrape(url)
        except Exception:
            parsed_url = urlparse(url)

            if parsed_url.hostname not in {"localhost", "127.0.0.1"}:
                raise

            docker_host_url = parsed_url._replace(netloc="host.docker.internal:3001")
            return super().scrape(urlunparse(docker_host_url))

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        price_element = soup.select_one('[data-testid="product-price"]')
        name_element = soup.select_one('[data-testid="product-title"]')
        image_element = soup.select_one('[data-testid="product-image"]')

        if not price_element:
            raise ValueError("Price not found on Dummy Store.")

        if not name_element:
            raise ValueError("Product title not found on Dummy Store.")

        return {
            "price_text": price_element.get_text(strip=True),
            "name": name_element.get_text(" ", strip=True),
            "image_url": image_element.get("src") if image_element else None,
            "store": "dummy",
        }
