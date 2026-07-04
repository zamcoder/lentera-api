<!doctype html>
<html lang="id">
  <body style="margin:0;background:#F6F4EC;font-family:-apple-system,Segoe UI,Roboto,sans-serif;color:#2C302A;">
    <div style="max-width:440px;margin:0 auto;padding:32px 24px;">
      <div style="font-size:20px;font-weight:600;color:#5C8166;margin-bottom:18px;">Lentera</div>
      <p style="font-size:15px;line-height:1.6;">Halo, ini kode verifikasimu:</p>
      <div style="font-size:34px;font-weight:700;letter-spacing:8px;color:#2C302A;background:#FBFAF4;border:1px solid rgba(40,45,35,.12);border-radius:12px;padding:16px 0;text-align:center;margin:16px 0;">
        {{ $code }}
      </div>
      <p style="font-size:13.5px;color:#6E7567;line-height:1.6;">
        Berlaku {{ $ttlMinutes }} menit. Jangan bagikan kode ini ke siapa pun —
        tim Lentera tak pernah memintanya. Abaikan email ini bila kamu tak
        memintanya.
      </p>
    </div>
  </body>
</html>
