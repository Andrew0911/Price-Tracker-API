from bs4 import BeautifulSoup
from typing import Dict, Optional
import re
from .base import BaseScraper


class IstyleScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        # Name: <div class="product__title"><h1>...</h1></div>
        name_el = soup.select_one(".product__title h1")
        name = name_el.get_text(strip=True) if name_el else None

        # Price: <span class="js-product-price-with-care-warranty" data-price="9 999,98 lei" data-price-raw="999998">
        price_el = soup.select_one(".js-product-price-with-care-warranty")
        price_text = None
        price = None
        if price_el:
            price_text = price_el.get("data-price") or price_el.get_text(strip=True)
            data_raw = price_el.get("data-price-raw")
            if data_raw and data_raw.isdigit():
                price = int(data_raw) / 100.0
            elif price_text:
                cleaned = re.sub(r"[^\d,\.]", "", price_text).replace(",", ".")
                try:
                    price = float(cleaned)
                except ValueError:
                    price = None

        if price_el is None:
            raise ValueError("Price not found on iStyle. The site structure may have changed.")

        # Image: first <img loading="eager"> inside media-gallery (the active/primary slide)
        image_url = None
        for sel in (
            "media-gallery img[loading='eager']",
            ".product__media img[loading='eager']",
            "media-gallery img",
        ):
            img = soup.select_one(sel)
            if img and img.get("src"):
                src = img["src"]
                if src.startswith("//"):
                    src = "https:" + src
                image_url = src
                break

        return {
            "name": name,
            "price": price,
            "price_text": price_text,
            "image_url": image_url,
            "store": "istyle",
        }