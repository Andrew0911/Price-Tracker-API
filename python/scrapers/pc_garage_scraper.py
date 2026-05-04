from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper

class PcGarageScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        sell_container = soup.find(id="product_sell_container")
        price_element = sell_container.find("span", class_="price_num") if sell_container else None

        if not price_element:
            raise ValueError("Price not found on PC Garage. The site structure may have changed.")

        price_text = price_element.get_text(strip=True)

        name_element = soup.find("h1", id="product_name")
        name = name_element.get_text(strip=True) if name_element else None

        image_element = soup.find("img", class_="pm_img", attrs={"itemprop": "image"})
        if image_element:
            image_url = image_element.get("data-large") or image_element.get("src")
        else:
            image_url = None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "pcgarage"
        }