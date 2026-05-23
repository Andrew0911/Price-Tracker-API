from urllib.parse import urlparse
from .emag_scraper import EmagScraper
from .altex_scraper import AltexScraper
from .flanco_scraper import FlancoScraper
from .pc_garage_scraper import PcGarageScraper
from .vexio_scraper import VexioScraper
from .evomag_scraper import EvomagScraper
from .istyle_scraper import IstyleScraper
from .answear_scraper import AnswearScraper
from .modivo_scraper import ModivoScraper
from .about_you_scraper import AboutYouScraper
from .fashion_days_scraper import FashionDaysScraper
from .dummy_store_scraper import DummyStoreScraper

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
    "istyle.ro": IstyleScraper(),
    "www.answear.ro": AnswearScraper(),
    "answear.ro": AnswearScraper(),
    "www.modivo.ro": ModivoScraper(),
    "modivo.ro": ModivoScraper(),
    "www.aboutyou.ro": AboutYouScraper(),
    "aboutyou.ro": AboutYouScraper(),
    "www.fashiondays.ro": FashionDaysScraper(),
    "fashiondays.ro": FashionDaysScraper(),
    "localhost:3001": DummyStoreScraper(),
    "127.0.0.1:3001": DummyStoreScraper(),
    "host.docker.internal:3001": DummyStoreScraper(),
}

def get_scraper_for_url(url: str):
    domain = urlparse(url).netloc
    scraper = SCRAPER_REGISTRY.get(domain)

    return scraper
