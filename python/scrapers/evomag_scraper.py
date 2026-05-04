from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper

class EvomagScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        name_element = soup.find("h1", class_="product_name")
        name = name_element.get_text(strip=True) if name_element else None

        price_element = soup.find("span", attrs={"itemprop": "price"})
        if not price_element:
            raise ValueError("Price not found on Evomag. The site structure may have changed.")
        currency_meta = soup.find("meta", attrs={"itemprop": "priceCurrency"})
        currency = currency_meta.get("content") if currency_meta else "RON"
        price_text = price_element.get("content", price_element.get_text(strip=True)) + " " + currency

        image_element = soup.select_one(".MainProductGallery img[loading='eager']")
        if image_element:
            image_url = image_element.get("data-zoom") or image_element.get("src")
        else:
            image_url = None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "evomag"
        }