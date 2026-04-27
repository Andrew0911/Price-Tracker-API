import requests
from bs4 import BeautifulSoup
from typing import Dict, Optional

class BaseScraper:
    def __init__(self):
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
        }

    def get_html(self, url: str) -> BeautifulSoup:
        """Download the page and return a BeautifulSoup object."""
        response = requests.get(url, headers=self.headers, timeout=10)
        response.raise_for_status()
        return BeautifulSoup(response.text, "html.parser")

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        """Each subclass must override this. Returns dict with 'price_text' and 'name'."""
        raise NotImplementedError("This method must be implemented in the subclass.")

    def scrape(self, url: str) -> Dict[str, Optional[str]]:
        """Main method that orchestrates the scraping."""
        soup = self.get_html(url)
        return self.extract_product_data(soup)