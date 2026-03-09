import urllib.request
import urllib.parse
import json
import time
import os
import sys

def download_and_extract(city_name, uf):
    print(f"Iniciando varredura inteligente de ruas no ViaCEP para: {city_name} - {uf}")
    
    # Prefixos mais comuns de logradouros no Brasil para maximizar a extração
    terms = [
        'Rua', 'Avenida', 'Av', 'Travessa', 'Vila', 'Jardim', 'Parque', 
        'Estância', 'Rodovia', 'Praça', 'Alameda', 'Caminho', 'Estrada', 
        'Loteamento', 'Recanto', 'Vale', 'Chácara', 'Sítio', 'Residencial',
        'Morada', 'Colina', 'Condomínio', 'Alto'
    ]

    ceps = set()
    
    for idx, term in enumerate(terms):
        print(f"[{idx+1}/{len(terms)}] Buscando por prefixo: '{term}'...")
        encoded_term = urllib.parse.quote(term)
        url = f"https://viacep.com.br/ws/{uf}/{urllib.parse.quote(city_name)}/{encoded_term}/json/"
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        
        try:
            with urllib.request.urlopen(req) as response:
                data = json.loads(response.read().decode('utf-8'))
                
                # Se não encontrar nada, o ViaCEP retorna lista vazia [] ou error no payload
                if isinstance(data, list):
                    found = 0
                    for item in data:
                        if 'cep' in item:
                            ceps.add(item['cep'])
                            found += 1
                    print(f"  -> Encontrados {found} novos CEPs")
        except Exception as e:
            print(f"  -> Erro ou nenhum resultado para '{term}': {e}")
            
        time.sleep(1) # Respeitar o limite de taxa do ViaCEP e não ser bloqueado

    if not ceps:
        # Tentar fallback para CEP único (cidades pequenas não setorizadas)
        print("Nenhum CEP por logradouro encontrado. Tentando obter o CEP único da cidade...")
        url = f"https://viacep.com.br/ws/{uf}/{urllib.parse.quote(city_name)}/Centro/json/"
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        try:
            with urllib.request.urlopen(req) as response:
                data = json.loads(response.read().decode('utf-8'))
                if isinstance(data, list) and len(data) > 0:
                    ceps.add(data[0]['cep'])
        except Exception:
            pass

    output_file = f"storage/app/{city_name.lower().replace(' ', '_')}_ceps.json"
    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(list(ceps), f, ensure_ascii=False, indent=2)
        
    print(f"\nSucesso absoluto! {len(ceps)} CEPs únicos de {city_name} extraídos para '{output_file}'.")
    return list(ceps)

if __name__ == "__main__":
    if len(sys.argv) > 2:
        download_and_extract(sys.argv[1], sys.argv[2])
    else:
        # Default para Jarinu pra uso fácil
        download_and_extract("Jarinu", "SP")
