from urllib.parse import urlparse
from .emag_scraper import EmagScraper
from .altex_scraper import AltexScraper
from .flanco_scraper import FlancoScraper
from .pc_garage_scraper import PcGarageScraper
from .vexio_scraper import VexioScraper
from .evomag_scraper import EvomagScraper
from .istyle_scraper import IstyleScraper

SCRAPER_REGISTRY = {
    "emag.ro": EmagScraper(),
    "www.emag.ro": EmagScraper(),
    "altex.ro": AltexScraper(),
    "www.altex.ro": AltexScraper(),
    "www.flanco.ro": FlancoScraper(),
    "flanco.ro": FlancoScraper(),
    "www.pcgarage.ro": PcGarageScraper(),
    "pcgarage.ro": PcGarageScraper(),
    "www.vexio.ro": VexioScraper(),
    "vexio.ro": VexioScraper(),
    "www.evomag.ro": EvomagScraper(), 
    "evomag.ro": EvomagScraper(),
    "www.istyle.ro": IstyleScraper(),
    "istyle.ro": IstyleScraper()
}

def get_scraper_for_url(url: str):
    domain = urlparse(url).netloc
    scraper = SCRAPER_REGISTRY.get(domain)

    return scraper