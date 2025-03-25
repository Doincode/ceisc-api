#!/bin/bash

# Token de autenticação (admin)
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiODBlZDBjMmYxOTBiNWU5YzkyMjA1YTM1Y2VhN2Y3YzQ5OTlkMTEyNGE2MjI3YTZmZDFhMGExODUyNWUyZTg5MjY5YzBjYTlmYmFjNWUxYmUiLCJpYXQiOjE3NDI0MTIyNjEuMzEwMjk1LCJuYmYiOjE3NDI0MTIyNjEuMzEwMjk5LCJleHAiOjE3NDM3MDgyNjEuMjgwNDQ2LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.houBRdMzF1h4nD6kBiEMtkZtLbCz7Oh56w-lG7N-4dTJpnY4zIJ1CBo3gL78Ty7T1Ed6LhxMQF3Q9R42twZJCbBqYiOFwJJQE9zfytJd3tgd0vy0i0CrMx21GMJd1M4Ceqt8YFLnGU18exGdcS-zRHB6k5rEVtXDkTasIIPHLksyhsoY8Pd0h35GdAOtOKGQ1zDi3XYxsv51cqTFHQv5kTn-mmrb4zCsmQ_Lhe99nFimn5FStL9vOeREA4UmlUr209-igeYuIQCdaOoiigwxTRGdYc8LyZ5zecbsIp6bTxruqbWaOcgd2TZzm62EywVPJAR7_4hTHlAiMPGt8n3pJoZdv9c872sBRCWTPpMBpJvTvER1hpUogBMWf_WKTuiWEFz00i3VwaRUxK9ouGqIkD6jvwrzgT-mwfGwcuFJOHuMl2fTa5Y93BBXIuCZY63TY6edL_3fxkxN1p0D7zojwZe9jJtESaJx1SoufpqR0pZNFpkpERWYegu4SQ0vJD5gWzzEw6HnxSlkwhNlZLiaFmFdHxBJJCTsXTs85Lme-ypFflXn-wIXpyHibWU-KUdXwLXu0UVdTPzUnWd1aMQoZHtxERp-0s8mGxa7ot0fcFGzzP7g4sya2b11YKv9GaVDiXMYf9PMKVurZoSXlVI002ysarf4_IK4zyjWat-ZuEk"

# URL base da API
API_URL="http://localhost:8000/api"

# Headers comuns
HEADERS=(-H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
PUBLIC_HEADERS=(-H "Accept: application/json")

echo "=== Testando rota para listar conteúdos premium ==="
echo "GET $API_URL/premium-content"
curl -s "${HEADERS[@]}" $API_URL/premium-content | head -30
echo -e "\n\n"

echo "=== Testando rota para listar assinaturas (admin) ==="
echo "GET $API_URL/admin/subscriptions"
curl -s "${HEADERS[@]}" $API_URL/admin/subscriptions | head -30
echo -e "\n\n"

echo "=== Testando rota para listar todos os conteúdos (com acesso a premium) ==="
echo "GET $API_URL/contents"
curl -s "${HEADERS[@]}" $API_URL/contents | head -30
echo -e "\n\n"

# Testando acesso sem autenticação (para verificar que conteúdos premium são filtrados)
echo "=== Testando rota de conteúdos sem autenticação (não deve mostrar premium) ==="
echo "GET $API_URL/contents"
curl -s "${PUBLIC_HEADERS[@]}" $API_URL/contents | head -30
echo -e "\n\n"

echo "Testes concluídos!" 