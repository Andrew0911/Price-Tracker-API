from bs4 import BeautifulSoup
from typing import Dict, Optional
from .base import BaseScraper

class AltexScraper(BaseScraper):

    # THIS SCRAPER USES PROXY
    def __init__(self):
        super().__init__()
        self.requires_proxy = True

    def extract_product_data(self, soup: BeautifulSoup) -> Dict[str, Optional[str]]:
        # Look for the red price (actual price), not the PRP

        # Find all divs with "text-red-brand"
        red_divs = soup.find_all("div", class_="text-red-brand")

        if not red_divs:
            raise ValueError("No div with class 'text-red-brand' found on Altex.")

        # Filter to find the one containing Price-int (not reviews)
        red_price_div = None
        for div in red_divs:
            price_int = div.find("span", class_="Price-int")
            if price_int:
                red_price_div = div
                break

        if not red_price_div:
            raise ValueError("Red price with Price-int not found on Altex.")

        # Extract integer part
        price_int_element = red_price_div.find("span", class_="Price-int")
        raw_price_int = price_int_element.get_text(strip=True)
        clean_price = raw_price_int.replace(".", "")

        # Extract decimal part
        price_dec_element = red_price_div.find("sup", class_="inline-block")

        if price_dec_element:
            raw_price_dec = price_dec_element.get_text(strip=True)
            clean_price += raw_price_dec.replace(",", ".")

        name_element = soup.find("h1", class_="product-title") or soup.find("h1")
        name = name_element.get_text(strip=True) if name_element else None

        image_element = soup.find("img", class_="product-image-photo") or soup.find("img", attrs={"alt": name}) if name else None
        if not image_element:
            image_element = soup.find("div", class_="product-media") 
            image_element = image_element.find("img") if image_element else None
        image_url = image_element["src"] if image_element and image_element.get("src") else None

        return {
            "price_text": clean_price,
            "name": name,
            "image_url": image_url,
            "store": "altex"
        }