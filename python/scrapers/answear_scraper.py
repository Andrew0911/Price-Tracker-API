from bs4 import BeautifulSoup
import json
from typing import Dict, Optional

from .base import BaseScraper


class AnswearScraper(BaseScraper):
    def _find_by_class_fragment(self, soup: BeautifulSoup, tag: str, fragment: str):
        return soup.find(
            tag,
            class_=lambda class_name: class_name and fragment in class_name,
        )

    def _get_product_schema(self, soup: BeautifulSoup) -> Optional[dict]:
        for script in soup.select('script[type="application/ld+json"]'):
            raw_json = script.string or script.get_text(strip=True)
            if not raw_json:
                continue

            try:
                payload = json.loads(raw_json)
            except json.JSONDecodeError:
                continue

            items = payload if isinstance(payload, list) else [payload]
            for item in items:
                if isinstance(item, dict) and item.get("@type") == "Product":
                    return item

        return None

    def _schema_price_text(self, schema: Optional[dict]) -> Optional[str]:
        if not schema:
            return None

        offers = schema.get("offers")
        if isinstance(offers, list):
            offers = next((offer for offer in offers if isinstance(offer, dict)), None)

        if not isinstance(offers, dict):
            return None

        price = offers.get("price")
        if price in (None, ""):
            return None

        currency = offers.get("priceCurrency") or "RON"
        return f"{price} {currency}"

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        product_schema = self._get_product_schema(soup)
        brand_element = soup.select_one('[data-test="productBrandLink"]')
        title_element = self._find_by_class_fragment(soup, "h1", "__detailsTitle__")
        subtitle_element = self._find_by_class_fragment(soup, "h2", "__detailsSubtitle__")

        title = title_element.get_text(" ", strip=True) if title_element else None
        subtitle = subtitle_element.get_text(" ", strip=True) if subtitle_element else None
        brand = brand_element.get_text(" ", strip=True) if brand_element else None

        name_parts = [part for part in (brand, title, subtitle) if part]
        name = " ".join(name_parts) if name_parts else None
        if not name and product_schema:
            name = product_schema.get("name")

        price_element = (
            soup.select_one('[data-test="regularPrice"]')
            or soup.select_one('[data-test="priceSaleWithMinimalDesktop"]')
            or soup.select_one(".ProductCardStylesProvider__priceSaleMinimal__OIiJM")
            or soup.select_one(".ProductCardStylesProvider__priceRegular__Z3Oi0")
        )
        price_text = self._schema_price_text(product_schema)
        if not price_text and price_element:
            price_text = price_element.get_text(" ", strip=True)

        if not price_text:
            raise ValueError("Price not found on Answear. The site structure may have changed.")

        image_element = (
            soup.select_one('[data-test="galleryCarouselImage_0Img"]')
            or soup.select_one('[data-test="gallery"] img')
            or soup.select_one("img[src]")
        )
        image_url = image_element.get("src") if image_element else None
        if not image_url and product_schema:
            schema_image = product_schema.get("image")
            if isinstance(schema_image, list) and schema_image:
                first_image = schema_image[0]
                if isinstance(first_image, dict):
                    image_url = first_image.get("contentUrl") or first_image.get("url")
                elif isinstance(first_image, str):
                    image_url = first_image
            elif isinstance(schema_image, str):
                image_url = schema_image

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "answear",
        }
