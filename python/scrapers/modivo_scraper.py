from bs4 import BeautifulSoup
from typing import Dict, Optional

from .base import BaseScraper


class ModivoScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        brand_element = soup.select_one('[data-test-id="product-name-link"]')
        name_element = soup.select_one('[data-test-id="product-name-details-heading"]')

        brand = brand_element.get_text(" ", strip=True) if brand_element else None
        product_name = name_element.get_text(" ", strip=True) if name_element else None
        name_parts = [part for part in (brand, product_name) if part]
        name = " ".join(name_parts) if name_parts else None

        price_element = (
            soup.select_one(".product-price-new .sr-only")
            or soup.select_one(".product-price-new .price-wrapper span")
        )
        if not price_element:
            raise ValueError("Price not found on Modivo. The site structure may have changed.")
        price_text = price_element.get_text(" ", strip=True)

        image_element = (
            soup.select_one('[data-test-id="main-gallery"] img')
            or soup.select_one(".product-main-gallery img")
            or soup.select_one("img[src]")
        )
        image_url = image_element.get("src") if image_element else None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "modivo",
        }
