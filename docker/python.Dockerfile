# Folosim o imagine oficială de Python
FROM python:3.11-slim

# Setăm directorul de lucru în container
WORKDIR /app

# Setăm variabile de mediu pentru a preveni fișierele .pyc și pentru loguri live
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

# Instalăm dependențele de sistem necesare pentru MySQL
RUN apt-get update \
    && apt-get install -y default-libmysqlclient-dev build-essential pkg-config \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copiem doar fișierul de dependențe întâi din folderul python
# Contextul este ".", deci calea este ./python/requirements.txt
COPY ./python/requirements.txt /app/

# Instalăm pachetele Python
RUN pip install --no-cache-dir -r requirements.txt

# Copiem restul codului din folderul python
COPY ./python /app/

# Comanda pentru a rula serverul FastAPI cu hot-reload
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]