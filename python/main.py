from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from scrapers import get_scraper_for_url
from price_extractor import extract_price_from_text

app = FastAPI()

class ScrapeRequest(BaseModel):
    productUrl: str

@app.post("/scrape/")
def scrape_product(request: ScrapeRequest):
    url = request.productUrl
    
    # 1. Găsim scraper-ul potrivit pentru acest link
    scraper = get_scraper_for_url(url)
    
    if not scraper:
        raise HTTPException(
            status_code=400, 
            detail=f"Domeniul din URL nu este suportat momentan."
        )
        
    try:
        priceText = scraper.scrape(url)
        price, currency = extract_price_from_text(priceText)

        return {
            "price": price,
            "currency": currency
        }
        
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Eroare la scraping: {str(e)}")