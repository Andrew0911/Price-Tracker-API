from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper

class VexioScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        price_element = soup.find("span", id="price-value")

        if not price_element:
            raise ValueError("Price not found on Vexio. The site structure may have changed.")

        price_text = price_element.get_text(strip=True)

        name_container = soup.find("div", class_="name")
        name_element = name_container.find("h1") if name_container else None
        name = name_element.get_text(strip=True) if name_element else None

        active_item = soup.select_one("#gallery-carousel .carousel-inner .item.active")
        image_element = active_item.find("img") if active_item else None
        image_url = image_element.get("src") if image_element else None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "vexio"
        }