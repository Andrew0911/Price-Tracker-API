import re
from typing import Optional, Tuple, Dict

# Currency symbol to code mapping
CURRENCY_MAPPING = {
    'lei': 'RON',
    'ron': 'RON',
    'eur': 'EUR',
    '€': 'EUR',
    'usd': 'USD',
    '$': 'USD',
    'gbp': 'GBP',
    '£': 'GBP',
    'jpy': 'JPY',
    '¥': 'JPY',
}

def normalize_price_string(text: str) -> str:
    """Normalize European price format (6.129,99) to standard float string (6129.99)."""
    # Keep only digits, dots and commas
    cleaned = re.sub(r'[^\d.,]', '', text.strip())

    if not cleaned:
        return cleaned

    # European format: dot as thousands separator, comma as decimal
    # e.g. 6.129,99 or 1.234.567,89
    if re.search(r'\d\.\d{3}(?:[,.]|$)', cleaned):
        cleaned = cleaned.replace('.', '').replace(',', '.')
    # Comma as decimal only (no dot present), e.g. 129,99
    elif ',' in cleaned and '.' not in cleaned:
        cleaned = cleaned.replace(',', '.')

    return cleaned


def parse_price(text: str) -> Optional[Tuple[float, str]]:
    if not text:
        return None
    
    try:        
        # Extract currency first
        currency = extract_currency_from_text(text)
        
        # Remove currency symbols
        text = re.sub(r'(Lei|RON|EUR|USD|€|\$|£|¥)', '', text, flags=re.IGNORECASE)

        # Normalize European number format before regex matching
        text = normalize_price_string(text)

        # Extract price patterns
        price_patterns = [
            r'(\d+)[.,]\s?(\d{2})',
            r'(\d+)\s?[.,]\s?(\d+)',
            r'(\d+(?:[.,]\d+)?)',
        ]
        
        for pattern in price_patterns:
            matches = re.findall(pattern, text)
            if matches:
                if isinstance(matches[0], tuple):
                    integer_part = matches[0][0]
                    decimal_part = matches[0][1] if len(matches[0]) > 1 else '0'
                    price_value = float(f"{integer_part}.{decimal_part}")
                else:
                    price_str = matches[0].replace(',', '.')
                    price_value = float(price_str)
                
                if price_value is not None and price_value > 0:
                    return (price_value, currency)
        
        return None
    except Exception as e:
        print(f"Error extracting price from text: {e}")
        return None


def extract_currency_from_text(text: str) -> str:
    text_lower = text.lower()
    
    for currency_symbol, currency_code in CURRENCY_MAPPING.items():
        if currency_symbol in text_lower:
            return currency_code
    
    return 'RON'

