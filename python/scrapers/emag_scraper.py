from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper

class EmagScraper(BaseScraper):
    
    # THIS SCRAPER USES PROXY
    def __init__(self):
        super().__init__()
        self.requires_proxy = True

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        price_element = soup.find("p", class_="product-new-price")

        if not price_element:
            raise ValueError("Price not found on eMAG. The site structure may have changed.")

        price_text = price_element.get_text(strip=True)

        name_element = soup.find("h1", class_="page-title")
        name = name_element.get_text(strip=True) if name_element else None

        image_element = soup.find("img", attrs={"data-test": "main-product-gallery"})
        image_url = image_element["src"] if image_element and image_element.get("src") else None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "emag"
        }