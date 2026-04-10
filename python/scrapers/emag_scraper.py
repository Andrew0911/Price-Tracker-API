from bs4 import BeautifulSoup
from .base import BaseScraper

class EmagScraper(BaseScraper):
    def extract_price(self, soup: BeautifulSoup) -> str:
        # eMAG folosește de obicei clasa 'product-new-price'
        price_element = soup.find("p", class_="product-new-price")
        
        if not price_element:
            raise ValueError("Nu am găsit prețul pe eMAG. Poate s-a schimbat site-ul?")
                
        return price_element.get_text(strip=True)