from bs4 import BeautifulSoup
from typing import Dict, Optional

from .base import BaseScraper


class AboutYouScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        name_element = soup.select_one('[data-testid="productName"]')
        brand_element = soup.select_one('[data-testid="brandNameContainer"]')

        name_text = name_element.get_text(" ", strip=True) if name_element else None
        brand = brand_element.get_text(" ", strip=True) if brand_element else None
        name = name_text
        if name_text and brand and brand not in name_text:
            name = f"{name_text} {brand}"

        price_element = (
            soup.select_one('[data-testid="finalPrice"] [data-testid="finalPrice"]')
            or soup.select_one('[data-testid="priceBox"] [data-testid="finalPrice"]')
        )
        if not price_element:
            raise ValueError("Price not found on About You. The site structure may have changed.")
        price_text = price_element.get_text(" ", strip=True)

        image_element = (
            soup.select_one('[data-testid="productImageGrid"] [data-testid="productImageView"]')
            or soup.select_one('[data-testid="productImageView"]')
            or soup.select_one("img[src]")
        )
        image_url = image_element.get("src") if image_element else None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "aboutyou",
        }
