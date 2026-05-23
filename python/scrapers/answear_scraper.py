from bs4 import BeautifulSoup
from typing import Dict, Optional

from .base import BaseScraper


class AnswearScraper(BaseScraper):
    def _find_by_class_fragment(self, soup: BeautifulSoup, tag: str, fragment: str):
        return soup.find(
            tag,
            class_=lambda class_name: class_name and fragment in class_name,
        )

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        brand_element = soup.select_one('[data-test="productBrandLink"]')
        title_element = self._find_by_class_fragment(soup, "h1", "__detailsTitle__")
        subtitle_element = self._find_by_class_fragment(soup, "h2", "__detailsSubtitle__")

        title = title_element.get_text(" ", strip=True) if title_element else None
        subtitle = subtitle_element.get_text(" ", strip=True) if subtitle_element else None
        brand = brand_element.get_text(" ", strip=True) if brand_element else None

        name_parts = [part for part in (brand, title, subtitle) if part]
        name = " ".join(name_parts) if name_parts else None

        price_element = soup.select_one('[data-test="regularPrice"]')
        if not price_element:
            raise ValueError("Price not found on Answear. The site structure may have changed.")
        price_text = price_element.get_text(" ", strip=True)

        image_element = (
            soup.select_one('[data-test="galleryCarouselImage_0Img"]')
            or soup.select_one('[data-test="gallery"] img')
            or soup.select_one("img[src]")
        )
        image_url = image_element.get("src") if image_element else None

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "answear",
        }
