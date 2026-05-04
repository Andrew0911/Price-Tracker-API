from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper


class FlancoScraper(BaseScraper):
    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        # Product name
        name_element = soup.find("span", {"itemprop": "name", "data-ui-id": "page-title-wrapper"})
        name = name_element.get_text(strip=True) if name_element else None

        # Price: the main product price box is inside .product-info-main.
        # Products on sale use .special-price, regular items use .singlePrice.
        price_text = None
        main_price_box = soup.select_one(".product-info-main .price-box")
        if main_price_box:
            special = main_price_box.select_one(".special-price .price")
            single = main_price_box.select_one(".singlePrice .price")
            price_element = special or single
            if price_element:
                # Combine base text and the <sup class="decimal"> to form e.g. "7.418,99 lei"
                base = price_element.get_text(separator="", strip=True)
                # get_text already includes the sup text, so just use base
                price_text = base

        if not price_text:
            raise ValueError("Price not found on Flanco. The site structure may have changed.")

        # Image: prefer the full-resolution href from the main gallery's first thumbnail link
        image_url = None
        gallery_link = soup.select_one("#product-main-image a.thumbnail[itemprop='contentUrl']")
        if gallery_link and gallery_link.get("href"):
            image_url = gallery_link["href"]
        else:
            # Fallback: schema.org image meta tag
            meta_image = soup.find("meta", {"itemprop": "image"})
            if meta_image and meta_image.get("content"):
                image_url = meta_image["content"]

        return {
            "price_text": price_text,
            "name": name,
            "image_url": image_url,
            "store": "flanco",
        }