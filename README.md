# KKTC Meydan

Kıbrıs'ın özgür tartışma ve topluluk meydanı.

KKTC'de yaşayan öğrencilerin, yerel halkın, çalışanların, işletmelerin ve ziyaretçilerin bilgi paylaşabileceği, soru sorabileceği, tartışabileceği ve yerel hizmetleri keşfedebileceği merkezi dijital topluluk platformu.

## Özellikler (planlanan ve mevcut)
- Şehir bazlı topluluklar (Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Karpaz)
- Üniversite toplulukları (YDÜ, DAÜ, UKÜ, LAÜ, GAÜ)
- Anonim ve hesaplı paylaşım
- İşletme/mekan profilleri
- İkinci el, iş ilanı, ev/yurt ilanları
- Etkinlik paylaşımı
- Reklam sistemi (kategori/şehir/üniversite hedefleme)

Detaylı ürün ve mimari dokümantasyonu için bkz. [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md), [ARCHITECTURE.md](ARCHITECTURE.md), [ROADMAP.md](ROADMAP.md).

## Teknoloji
- PHP 8.2
- MariaDB 10.11
- nginx
- Docker / Docker Compose

## Kurulum (Docker)

```bash
git clone https://github.com/ERAYQ1/KKTC_Meydan.git
cd KKTC_Meydan
cp config.example.php config.php
# config.php içindeki veritabanı bilgilerini düzenleyin
docker compose up -d --build
composer install
```

Uygulama varsayılan olarak `http://localhost:8080` üzerinden erişilebilir olacaktır.

## Lisans
MIT — bkz. [LICENSE](LICENSE)
