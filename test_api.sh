#!/bin/bash

# Token de admin para testes
ADMIN_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiODBlZDBjMmYxOTBiNWU5YzkyMjA1YTM1Y2VhN2Y3YzQ5OTlkMTEyNGE2MjI3YTZmZDFhMGExODUyNWUyZTg5MjY5YzBjYTlmYmFjNWUxYmUiLCJpYXQiOjE3NDI0MTIyNjEuMzEwMjk1LCJuYmYiOjE3NDI0MTIyNjEuMzEwMjk5LCJleHAiOjE3NDM3MDgyNjEuMjgwNDQ2LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.houBRdMzF1h4nD6kBiEMtkZtLbCz7Oh56w-lG7N-4dTJpnY4zIJ1CBo3gL78Ty7T1Ed6LhxMQF3Q9R42twZJCbBqYiOFwJJQE9zfytJd3tgd0vy0i0CrMx21GMJd1M4Ceqt8YFLnGU18exGdcS-zRHB6k5rEVtXDkTasIIPHLksyhsoY8Pd0h35GdAOtOKGQ1zDi3XYxsv51cqTFHQv5kTn-mmrb4zCsmQ_Lhe99nFimn5FStL9vOeREA4UmlUr209-igeYuIQCdaOoiigwxTRGdYc8LyZ5zecbsIp6bTxruqbWaOcgd2TZzm62EywVPJAR7_4hTHlAiMPGt8n3pJoZdv9c872sBRCWTPpMBpJvTvER1hpUogBMWf_WKTuiWEFz00i3VwaRUxK9ouGqIkD6jvwrzgT-mwfGwcuFJOHuMl2fTa5Y93BBXIuCZY63TY6edL_3fxkxN1p0D7zojwZe9jJtESaJx1SoufpqR0pZNFpkpERWYegu4SQ0vJD5gWzzEw6HnxSlkwhNlZLiaFmFdHxBJJCTsXTs85Lme-ypFflXn-wIXpyHibWU-KUdXwLXu0UVdTPzUnWd1aMQoZHtxERp-0s8mGxa7ot0fcFGzzP7g4sya2b11YKv9GaVDiXMYf9PMKVurZoSXlVI002ysarf4_IK4zyjWat-ZuEk"

# Configuração de endpoints
BASE_URL="http://localhost:8000/api"
ADMIN_SUBSCRIPTIONS="${BASE_URL}/admin/subscriptions"
PREMIUM_CONTENT="${BASE_URL}/premium-content"
ALL_CONTENTS="${BASE_URL}/contents"

# Cabeçalhos
AUTH_HEADER="Authorization: Bearer ${ADMIN_TOKEN}"
CONTENT_HEADER="Content-Type: application/json"

echo "===== Testando a rota de listar assinaturas (admin) ====="
curl -s -H "${AUTH_HEADER}" -H "${CONTENT_HEADER}" ${ADMIN_SUBSCRIPTIONS}
echo -e "\n\n"

echo "===== Testando a rota de listar conteúdos premium ====="
curl -s -H "${AUTH_HEADER}" -H "${CONTENT_HEADER}" ${PREMIUM_CONTENT}
echo -e "\n\n"

echo "===== Testando a rota de listar todos os conteúdos (com admin) ====="
curl -s -H "${AUTH_HEADER}" -H "${CONTENT_HEADER}" ${ALL_CONTENTS}
echo -e "\n\n"

echo "===== Testando a rota de listar todos os conteúdos (sem autenticação, deve filtrar premium) ====="
curl -s -H "${CONTENT_HEADER}" ${ALL_CONTENTS}
echo -e "\n\n"

# Mais testes podem ser adicionados conforme necessário 