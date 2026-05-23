from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from scrapers import get_scraper_for_url
from price_extractor import parse_price

app = FastAPI()

class ScrapeRequest(BaseModel):
    url: str

@app.post("/scrape/")
def scrape_product(request: ScrapeRequest):
    url = request.url

    scraper = get_scraper_for_url(url)

    if not scraper:
        raise HTTPException(
            status_code=400,
            detail="The domain from the provided URL is not supported."
        )

    try:
        raw_data = scraper.scrape(url)
        parsed_price = parse_price(raw_data["price_text"])
        if not parsed_price:
            raise ValueError("Price not found.")

        price, currency = parsed_price

        return {
            "name": raw_data["name"],
            "price": price,
            "currency": currency,
            "image_url": raw_data["image_url"],
            "store": raw_data["store"]
        }

    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Scraping error: {str(e)}")
