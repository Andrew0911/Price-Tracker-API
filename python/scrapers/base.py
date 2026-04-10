import requests
from bs4 import BeautifulSoup

class BaseScraper:
    def __init__(self):
        # Setăm un User-Agent general
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
        }

    def get_html(self, url: str) -> BeautifulSoup:
        """Descarcă pagina și o transformă în obiect BeautifulSoup."""
        response = requests.get(url, headers=self.headers, timeout=10)
        response.raise_for_status()
        return BeautifulSoup(response.text, "html.parser")

    def extract_price(self, soup: BeautifulSoup) -> str:
        """Fiecare site va suprascrie această metodă!"""
        raise NotImplementedError("Această metodă trebuie implementată în clasa copil.")

    def scrape(self, url: str) -> str:
        """Metoda principală care orchestrează totul."""
        soup = self.get_html(url)
        return self.extract_price(soup)