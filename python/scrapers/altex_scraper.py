from bs4 import BeautifulSoup
from .base import BaseScraper

class AltexScraper(BaseScraper):
    def extract_price(self, soup: BeautifulSoup) -> str:
        # Căutăm prețul roșu (prețul real), nu PRP-ul

        # Căutăm toate divurile cu "text-red-brand"
        red_divs = soup.find_all("div", class_="text-red-brand")
        
        if not red_divs:
            raise ValueError("Nu am găsit nicio div cu clasa 'text-red-brand' pe Altex.")
        
        # Filtrăm pentru a găsi cel care conține Price-int (nu recenzii)
        red_price_div = None
        for div in red_divs:
            price_int = div.find("span", class_="Price-int")
            if price_int:
                red_price_div = div
                break
        
        if not red_price_div:
            raise ValueError("Nu am găsit prețul roșu cu Price-int pe Altex.")
        
        # Extragere preț întreg
        price_int_element = red_price_div.find("span", class_="Price-int")
        raw_price_int = price_int_element.get_text(strip=True)
        clean_price = raw_price_int.replace(".", "")
        
        # Extragere zecimale
        price_dec_element = red_price_div.find("sup", class_="inline-block")
        
        if price_dec_element:
            raw_price_dec = price_dec_element.get_text(strip=True)
            clean_price_dec = raw_price_dec.replace(",", ".")
            clean_price += clean_price_dec

        return clean_price