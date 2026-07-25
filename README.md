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
# config.php içindeki veritabanı bilgilerini düzenleyin (bu dosya .gitignore'da, asla commit etmeyin)
docker compose up -d --build
docker compose exec flarum-app composer install
```

Uygulama varsayılan olarak `http://localhost:8080` üzerinden erişilebilir olacaktır. `public/assets/` içindeki derlenmiş JS/CSS dosyaları git'e dahil değildir (`.gitignore`'da) — Flarum bunları ilk istekte kendiliğinden derler; forum sayfasını ve admin panelini (giriş yaptıktan sonra) birer kez ziyaret etmek yeterlidir.

İlk kurulumdan sonra gerekli eklentileri etkinleştirin:

```bash
docker compose exec flarum-app php flarum extension:enable flarum-tags flarum-lang-turkish \
  flarum-subscriptions flarum-likes flarum-lock flarum-mentions flarum-markdown \
  flarum-bbcode flarum-emoji flarum-suspend flarum-sticky flarum-flags flarum-approval
```

Kategoriler (tag) ve site ayarları (isim, açıklama, tema rengi, hoş geldin mesajı) `site_settings.json` içinde tanımlıdır ve `seed.php` betiği ile otomatik uygulanır:

```bash
docker compose exec flarum-app php seed.php
```

Bu betik idempotenttir (tekrar tekrar çalıştırılabilir), `site_settings.json`'daki ayarları ve kategorileri uygular, ayrıca her kategoride örnek kullanıcı ve konu oluşturarak forumun boş görünmesini engeller. Admin şifresi gibi kimlik bilgileri hiçbir dosyada saklanmaz — kurulum sonrası admin panelinden değiştirin.

### Durum
- [x] Docker altyapısı (nginx + php-fpm + MariaDB)
- [x] Flarum çekirdek kurulumu
- [x] Türkçe dil paketi etkin
- [x] Kategoriler: Gündem, Üniversiteler, Emlak, İkinci El, Ulaşım, Serbest Meydan
- [x] Seed betiği (site ayarları, kategoriler, örnek kullanıcı/konu)
- [x] Özel roller (Öğrenci, İşletme, Yerel Halk, Güvenilir Üye — şimdilik sadece rozet, ek izin yok, admin en yetkili grup olarak kalıyor)
- [x] Şehir/üniversite hashtag'leri (ikincil etiket olarak, kategoriyle birlikte kullanılıyor) + gerçek KKTC konularıyla genişletilmiş örnek içerik
- [ ] İlan/reklam sistemi (özel geliştirme gerektiriyor)
- [ ] Canlı hosting kurulumu

## Lisans
MIT — bkz. [LICENSE](LICENSE)
