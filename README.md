# Using #
- PayPal-PHP-SDK-1.11.0
- PayPal-PHP-SDK-1.14.0
- Rest API

# Get an Access Token - cURL #
curl -v https://api.sandbox.paypal.com/v1/oauth2/token \
   -H "Accept: application/json" \
   -H "Accept-Language: en_US" \
   -u "AbqfolVlfz83oAeZmhbztPaZaBZV7uH62w5SYVtLpaeRhD_2IPKrQw2Sc3YRhr8PSGFjYSOoyVUG5tZI:EKvSawiTVuOiHddWNuW-dTxkR01n5ZqZfvI08w3xohNXJljEVihrmkOGRt1TInQaSmTn7obxQwpxV8Dw" \
   -d "grant_type=client_credentials"

# Refund payments - cURL #
curl -v -X POST https://api.sandbox.paypal.com/v1/payments/sale/59C10820M44455011/refund \
-H "Content-Type: application/json" \
-H "Authorization: Bearer A21AALL2l3nkMKKfPpWSGz_kqIpdp3_hACVlJMlHPFX1x7bVxLr_HNX8cB6Kv8z0xWaJf05S5R7J2sLIH1OuYp0H6mK6FmYAg" \
-d '{
  "amount": {
    "total": "14.00",
    "currency": "CAD"
  }
}'

# Refund captured payment - cURL #
curl -v -X POST https://api.sandbox.paypal.com/v2/payments/captures/24113989WK442811F/refund \
-H "Content-Type: application/json" \
-H "Authorization: Bearer A21AAKM7oI5vg63ZTGJQret-Y2qDtKG_Ngr-bVB_PRg6rZfVSMTd1vc3w-w1KkU-hj5ZUqirZ8M3svPZ4dkAeRNtyhngAnP0g" \
-d '{
  "amount": {
    "value": "1314.00",
    "currency_code": "CAD"
  },
  "invoice_id": "26778",
  "note_to_payer": "Defective product"
}'