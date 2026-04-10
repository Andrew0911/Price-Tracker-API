from urllib.parse import urlparse
from .emag_scraper import EmagScraper
from .altex_scraper import AltexScraper

SCRAPER_REGISTRY = {
    "emag.ro": EmagScraper(),
    "www.emag.ro": EmagScraper(),
    "altex.ro": AltexScraper(),
    "www.altex.ro": AltexScraper(),
}

def get_scraper_for_url(url: str):
    domain = urlparse(url).netloc
    scraper = SCRAPER_REGISTRY.get(domain)

    return scraper