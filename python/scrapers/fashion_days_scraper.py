from bs4 import BeautifulSoup
from typing import Dict, Optional
import re

from .base import BaseScraper


class FashionDaysScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        brand_element = soup.select_one(".product-brand-name a") or soup.select_one(".product-brand-name")
        name_element = soup.select_one(".product-brand-desc")

        brand = brand_element.get_text(" ", strip=True) if brand_element else None
        product_name = name_element.get_text(" ", strip=True) if name_element else None
        name_parts = [part for part in (brand, product_name) if part]
        name = " ".join(name_parts) if name_parts else None

        price_element = soup.select_one(".sale-price.new-price")
        if not price_element:
            raise ValueError("Price not found on Fashion Days. The site structure may have changed.")

        decimal_element = price_element.select_one(".price__decimals")
        if decimal_element:
            integer_text = "".join(price_element.find_all(string=True, recursive=False))
            integer_part = re.sub(r"\D", "", integer_text)
            decimal_part = re.sub(r"\D", "", decimal_element.get_text(strip=True))
            price_text = f"{integer_part},{decimal_part} lei"
        else:
            price_text = price_element.get_text(" ", strip=True)

        image_element = (
            soup.select_one("#product-photo > img[src]")
            or soup.select_one("#product-photo img[data-zoom-image]")
            or soup.select_one(".product-photo-wrapper img[src]")
            or soup.select_one("img[src]")
        )
        image_url = None
        if image_element:
            image_url = image_element.get("data-zoom-image") or image_element.get("data-detail-image") or image_element.get("src")

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "fashiondays",
        }
