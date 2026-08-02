# 🌴 KKTC Meydan — Platform Kodu & Altyapısı

Kıbrıs'ın özgür tartışma, haber, ulaşım ve topluluk platformu.

KKTC'de yaşayan öğrencilerin, yerel halkın, çalışanların, işletmelerin ve ziyaretçilerin bilgi paylaşabileceği, soru sorabileceği, tartışabileceği ve yerel hizmetleri keşfedebileceği merkezi dijital topluluk platformu.

## Vizyon

KKTC Meydan, günlük gündemden bölge sohbetlerine, üniversite yaşamından emlak/iş ilanlarına, ulaşımdan altyapı sorunu bildirimine kadar Kıbrıs'la ilgili her konunun tek bir çatı altında toplandığı bir dijital meydan olmayı hedefler. Kategoriler admin tarafından sabit tutulur, kullanıcılar hashtag ile detaylandırır — böylece "Girne", "Girne Bölgesi", "Kyrenia" gibi yinelenen kategori kirliliği oluşmaz.

## 8 Ana Kategori

| Kategori | Kapsam |
|---|---|
| 🏛️ KKTC Gündem | Haberler, resmi duyurular, kamu hizmetleri, ekonomi, siyaset, eğitim/sağlık gündemi |
| 📍 Bölgeler | Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke + tüm alt yerleşimler |
| 🎓 Üniversiteler | Tercih, kampüs yaşamı, sınav, burs, yatay/dikey geçiş, yurt, ev arkadaşı |
| 🚌 Ulaşım | Ercan Havalimanı, otobüs saatleri, taksi/araç paylaşımı, trafik, kaza/tehlike bildirimi |
| 🏠 Yaşam | Kiralık/satılık ev, öğrenci evi, iş ilanı, staj, ikinci el eşya |
| 🍔 Keşfet | Restoran/kafe önerileri, etkinlikler, gezi ve turizm |
| 🛠️ Sorun Bildir | Altyapı/belediye sorunları, durum takibi (bildirildi → inceleniyor → yetkiliye iletildi → çözüldü) |
| 🗣️ Genel Meydan | Günlük sohbetler, mizah, itiraf, tartışma, soru-cevap |

## Özel Eklentiler

Bu repo'ya özel geliştirilen eklentiler `extensions/` klasöründe tutulur:

- **`extensions/business-profile`** — İşletme hesaplarına adres/telefon/WhatsApp/çalışma saati ekler, profilde herkese açık gösterir.
- **`extensions/report-status`** — Sorun Bildir konularına gerçek `report_status` alanı, renkli durum rozeti (🔴 bildirildi / 🟡 inceleniyor / 🔵 yetkiliye iletildi / 🟢 çözüldü) ve moderatör paneli dropdown'ı ekler.
- **`extensions/classifieds`** — İlan konularına yapılandırılmış `price`/`currency`/`location`/`contactPhone`/`classifiedType` alanları ekler (İlan Sistemi v2 altyapısı). Composer'da "Yaşam" veya ilan hashtag'lerinden biri seçilince fiyat/konum/iletişim formu belirir; liste ve detay sayfasında fiyat/konum rozeti gösterir.
- **`extensions/event-calendar`** — Etkinlik konularına yapılandırılmış `eventStartAt`/`eventEndAt` alanları ekler (Etkinlik Takvimi altyapısı). Composer'da "Keşfet" veya etkinlik hashtag'lerinden biri seçilince başlangıç/bitiş tarihi formu belirir; liste ve detay sayfasında `📅 DD.MM.YYYY HH:mm` rozeti gösterir.
- **`extensions/anonymous-posting`** — Yalnızca "Genel Meydan" kategorisinde 🕵️ anonim paylaşım. Telefon/doxxing regex filtresi (moderatör onay kuyruğuna düşürür), küfür/hakaret filtresi (gönderiyi reddeder), gerçek yazar bilgisi API seviyesinde herkesten gizlenir — sadece `discussion.viewIpsPosts` izni olan moderatörler `[Anonim] kullanıcı (IP: x.x.x.x)` biçiminde gerçek kimliği görebilir.
- **`extensions/ads-manager`** — Kategori/bölge/üniversite hedefli reklam banner sistemi. Admin panelinde tam CRUD sayfası (görsel URL, yönlendirme linki, hedef seçimi), forumda konu listesinin üstünde gösterim, gösterim/tıklama sayacı.
- **`extensions/analytics-dashboard`** — Admin paneline DAU/WAU, toplam konu/mesaj/üye sayaçları ve en popüler kategoriler (konu/yanıt bazında) gösteren salt-okunur istatistik sayfası.
- **`extensions/auto-moderation`** — Sağlık/Güvenlik-Acil Durum/Kamu kategorilerinde kimlik numarası, sağlık mahremiyeti ihlali ve kurum/meslek hedefli karalama içeriğini tespit edip moderatör kuyruğuna düşüren, güvenlik ihlallerinde otomatik `flarum-flags` raporu oluşturan arka plan koruma katmanı.
- **`extensions/mobile-ui`** — Mobil (iOS Safari & Android Chrome) için native-app hissi: alt navigasyon barı, kart tipi konu listesi, keşfet/profil bottom sheet'leri, tam ekran arama, klavye/dokunma uyumluluğu. Tüm kurallar `@media (max-width: 768px)` altında scope edilir — masaüstü görünümü etkilenmez.
- **`extensions/duty-pharmacy`** — Nöbetçi eczaneler (KTEB canlı veri, kaynak erişilemezse fallback) ve acil numaralar; forum üst barında modal.
- **`extensions/currency-ticker`** — GBP/EUR/USD → TRY canlı döviz kuru bandı; forum üst bar.
- **`extensions/block-user`** — Kullanıcı engelleme: profil, ayarlar sayfası ve engellenen kullanıcının içeriğini filtreleme.
- **`extensions/social-share`** — Konu içi hızlı paylaşım modalı: WhatsApp, X, Facebook ve link kopyala.
- **`fof/reactions`** — KKTC'ye özel 5 yerel emoji ile beğeni yerine geçen reaksiyon sistemi (emoji seti admin panelinden yapılandırılır).

Derlenmiş JS (`js/dist/`) repo'ya dahildir, `node_modules/` değildir; JS kodunda değişiklik yaparsan ilgili eklentinin `js/` klasöründe `npm install && npm run build` çalıştırman gerekir.

## Teknoloji

- PHP 8.2 (motor: [Flarum](https://flarum.org) — özelleştirilmiş forum çekirdeği üzerine inşa edildi)
- MariaDB 10.11
- nginx
- Node.js (özel eklentilerin JS derlemesi için)
- Docker / Docker Compose

### Nginx güvenlik sertleştirmesi

`.nginx.conf` içinde uygulanan kalkanlar:

- **RCE sertleştirmesi:** `/assets/` (kullanıcı yüklemeli dosya dizini) altında PHP çalıştırma engellenir — kötü amaçlı `.php` yüklemesi upload edilse bile çalıştırılamaz.
- **Hassas dosya erişim engeli:** `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `phpunit.xml`, `phpstan.neon` dosyalarına doğrudan HTTP erişimi 403 ile reddedilir.
- **Gizli dosya/dizin engeli:** `.` ile başlayan tüm dosyalar (`.env`, `.git` vb.) erişime kapalı.
- **Source map engeli:** `.map` dosyaları production'da bilgi sızıntısı olduğundan erişime kapalı.
- **Güvenlik başlıkları:** `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security` ve Flarum/Mithril boot ile YouTube gömme uyumlu bir `Content-Security-Policy` her yanıtta (`always`, 4xx/5xx dahil) gönderilir.
- **`server_tokens off`:** nginx sürüm bilgisi hata sayfalarında/başlıklarda gizlenir.

## Kurulum (tek komutla, Docker)

```bash
git clone https://github.com/ERAYQ1/KKTC_Meydan.git
cd KKTC_Meydan
cp config.example.php config.php
cp .env.example .env
# config.php'deki 'password' ve .env'deki MYSQL_ROOT_PASSWORD/MYSQL_PASSWORD
# değerlerini AYNI gerçek şifreyle doldurun (her iki dosya da .gitignore'da,
# asla commit etmeyin) — docker-compose.yml DB kullanıcı şifresini .env'den
# okur, config.php ise uygulamanın o kullanıcıyla bağlanmak için kullandığı
# şifredir; ikisi eşleşmezse uygulama DB'ye bağlanamaz.
docker compose up -d --build
docker compose exec flarum-app composer install
```

Platform varsayılan olarak `http://localhost:8080` üzerinden erişilebilir olacaktır. `public/assets/` içindeki derlenmiş JS/CSS dosyaları git'e dahil değildir (`.gitignore`'da) — ilk istekte kendiliğinden derlenir; forum sayfasını ve admin panelini (giriş yaptıktan sonra) birer kez ziyaret etmek yeterlidir.

> **Admin girişi:** `admin` kullanıcı adı/şifresi Flarum'un web kurulum sihirbazında (ilk `http://localhost:8080` ziyaretinde) interaktif olarak belirlenir; hiçbir dosyada varsayılan veya sabit kodlanmış bir admin şifresi tutulmaz. SMTP henüz yapılandırılmadığından ("Durum" bölümüne bakın) şifremi unuttum akışı şu an çalışmıyor — şifreyi unutursanız tekrar hatırlamanın yolu yok, admin panelinden değiştirip güvenli saklayın.

İlk kurulumdan sonra gerekli eklentileri etkinleştirin:

```bash
docker compose exec flarum-app php flarum extension:enable flarum-tags flarum-lang-turkish \
  flarum-subscriptions flarum-likes flarum-lock flarum-mentions flarum-markdown \
  flarum-bbcode flarum-emoji flarum-suspend flarum-sticky flarum-flags flarum-approval \
  kktcmeydan-business-profile kktcmeydan-report-status kktcmeydan-classifieds \
  kktcmeydan-event-calendar kktcmeydan-anonymous-posting kktcmeydan-ads-manager \
  kktcmeydan-analytics-dashboard kktcmeydan-auto-moderation kktcmeydan-mobile-ui \
  kktcmeydan-duty-pharmacy kktcmeydan-currency-ticker kktcmeydan-block-user \
  kktcmeydan-social-share fof-reactions \
  fof-follow-tags ianm-follow-users fof-sitemap fof-polls fof-oauth \
  fof-gamification fof-formatting fof-best-answer fof-pages fof-terms fof-seo
```

> `fof-follow-tags` doğrudan `composer.json`'da listelenmez; `ianm/follow-users`'ın bağımlılığı olarak kurulur ve ayrıca etkinleştirilmesi gerekir.

## Test ve doğrulama

```bash
# Bir kereye mahsus: AYRI test veritabanını kurar (hedef DB'deki tüm tabloları siler)
# DB_PASSWORD, .env'deki MYSQL_PASSWORD ile aynı olmalı
docker compose exec flarum-app sh -c 'DB_HOST=flarum-db DB_DATABASE=kktc_meydan_test \
  DB_USERNAME=kktc_user DB_PASSWORD=kktc_user_secret \
  FLARUM_TEST_TMP_DIR=/var/www/html/tests/tmp composer test:setup'

# Regresyon testleri (unit + entegrasyon)
docker compose exec flarum-app vendor/bin/phpunit

# Çalışan siteye karşı gerçek HTTP ile anonimlik sızıntısı taraması
docker compose exec flarum-app php scripts/verify-anonymity.php
```

13 özel eklentinin tamamı entegrasyon testi ile kapsanmıştır (`tests/integration/`): API nitelikleri, endpoint yanıtları, yetki sınırları ve — salt ön yüz olan `social-share`/`mobile-ui` için — ön yüzün eklenti etkinken sorunsuz boot etmesi.

`phpunit.xml` içinde `processIsolation="true"` **zorunludur, hız tercihi değil**: her entegrasyon testi tam bir Flarum uygulaması boot ettiği için tek süreçte ~8 testten sonra PHP bellek limiti tükeniyor ve MariaDB OOM-killer tarafından öldürülüyor.

Üçüncü parti (`fof/*`, `ianm/*`) paketler sadece İngilizce dil dosyasıyla gelir; Türkçe çevirileri `locale-overrides/tr.yml` içinde (kök `extend.php` ile `Extend\Locales` olarak bağlı) tamamlanmıştır — Flarum birden fazla locale dizinini aynı dile birleştirdiği için bu, paketlerin kendi çevirisiymiş gibi devreye girer.

Kategoriler (tag), site adı/açıklaması, tema rengi, hoş geldin mesajı ve alt bilgi metni `site_settings.json` içinde tanımlıdır ve `seed.php` betiği ile otomatik uygulanır:

```bash
docker compose exec flarum-app php seed.php
```

Bu betik idempotenttir (tekrar tekrar çalıştırılabilir), `site_settings.json`'daki ayarları ve kategorileri uygular, ayrıca her kategoride örnek kullanıcı ve konu oluşturarak platformun boş görünmesini engeller. Admin şifresi gibi kimlik bilgileri hiçbir dosyada saklanmaz — kurulum sonrası admin panelinden değiştirin.

### Durum

- [x] Docker altyapısı (nginx + php-fpm + MariaDB + Node.js)
- [x] Platform çekirdek kurulumu ve white-label marka ayarları (site adı, açıklama, hoş geldin mesajı, alt bilgi)
- [x] Türkçe dil paketi etkin
- [x] 8 ana kategori: KKTC Gündem, Bölgeler, Üniversiteler, Ulaşım, Yaşam, Keşfet, Sorun Bildir, Genel Meydan
- [x] Seed betiği (site ayarları, kategoriler, örnek kullanıcı/konu)
- [x] Özel roller (Öğrenci, İşletme, Yerel Halk, Güvenilir Üye — şimdilik sadece rozet, ek izin yok, admin en yetkili grup olarak kalıyor)
- [x] Bölge/üniversite/konu hashtag'leri (ikincil etiket olarak, kategoriyle birlikte kullanılıyor) + gerçek KKTC konularıyla genişletilmiş örnek içerik
- [x] Sorun Bildir sistemi v1 (durum hashtag'leriyle)
- [x] Sorun Bildir sistemi v2 (özel eklenti — bkz. `extensions/report-status`: gerçek `report_status` alanı, renkli rozet, moderatör dropdown'ı)
- [x] İlan sistemi v1 (hashtag + standart format konvansiyonuyla)
- [x] İlan sistemi v2 altyapısı (özel eklenti — bkz. `extensions/classifieds`: gerçek fiyat/konum/iletişim/ilan-türü alanları, composer formu, fiyat/konum rozeti)
- [x] İşletme dizini v1 (özel eklenti — bkz. `extensions/business-profile`)
- [x] Etkinlik takvimi altyapısı (özel eklenti — bkz. `extensions/event-calendar`: `eventStartAt`/`eventEndAt` alanları, composer formu, tarih rozeti)
- [x] Anonim paylaşım (özel eklenti — bkz. `extensions/anonymous-posting`: sadece Genel Meydan, telefon/küfür filtresi, sunucu taraflı kimlik maskeleme, moderatör-only ifşa)
- [x] Topluluk paketleri: en iyi cevap (`fof/best-answer`), anket (`fof/polls`), kullanıcı takibi (`ianm/follow-users`), Google OAuth altyapısı (`fof/oauth` — gerçek Client ID/Secret admin panelinden girilmeli), sitemap (`fof/sitemap`), YouTube/medya gömme (`fof/formatting`), rozet/itibar (`fof/gamification`) — hepsi kurulu, etkin, Türkçe çevirileri tamam
- [x] Reklam sistemi (özel eklenti — bkz. `extensions/ads-manager`: hedefli banner, admin CRUD paneli, gösterim/tıklama sayacı)
- [x] Detaylı istatistik/analitik admin paneli (özel eklenti — bkz. `extensions/analytics-dashboard`: DAU/WAU, popüler kategoriler, toplam sayaçlar)
- [x] Otomatik moderasyon (özel eklenti — bkz. `extensions/auto-moderation`: kimlik no/sağlık mahremiyeti/karalama tespiti, Sağlık-Güvenlik-Kamu kategorilerinde)
- [x] Nöbetçi eczaneler (özel eklenti — bkz. `extensions/duty-pharmacy`: KTEB canlı veri + fallback, acil numaralar, üst bar modalı)
- [x] Canlı döviz kuru bandı (özel eklenti — bkz. `extensions/currency-ticker`: GBP/EUR/USD → TRY, üst bar)
- [x] Kullanıcı engelleme (özel eklenti — bkz. `extensions/block-user`: profil, ayarlar, içerik filtreleme)
- [x] Konu paylaşım modalı (özel eklenti — bkz. `extensions/social-share`: WhatsApp, X, Facebook, link kopyala)
- [x] KKTC yerel emoji reaksiyonları (`fof/reactions`, 5 yerel emoji)
- [x] Nginx güvenlik sertleştirmesi (`/assets/` altında PHP çalıştırma yasağı, hassas config dosyalarına 403, source map/gizli dosya engeli, güvenlik başlıkları — bkz. Teknoloji bölümü)
- [x] SEO meta/OG/Twitter Card/schema.org etiketleri (`fof/seo`), robots.txt (`fof/sitemap` tarafından zaten otomatik üretiliyordu)
- [x] Gizlilik Politikası + Kullanım Şartları sayfaları (`fof/pages` ile `/p/gizlilik-politikasi` ve `/p/kullanim-sartlari`), kayıtta zorunlu kabul checkbox'ı (`fof/terms`) — metinler `seed.php` tarafından tohumlanıyor (yayın metni, iletişim adresi `iletisim@kktcmeydan.com`); yayına almadan önce hukuki gözden geçirme önerilir
- [x] Örnek reklam banner'ları (`extensions/ads-manager`, görseller `public/assets/ads/*.svg`) ve varsayılan gönderen adresi `mail_from` — hepsi `seed.php` ile idempotent tohumlanıyor
- [x] N+1 sorgu düzeltmeleri (konu listesinde `ianm/follow-users` + `fof/best-answer` kaynaklı gereksiz sorgular — bkz. kök `extend.php`) ve rozetlerin başlığın üzerine binmesi düzeltmesi (bkz. `less-overrides/forum.less`)
- [ ] Mail/SMTP ayarı (kayıt onayı ve şifre sıfırlama şu an çalışmıyor — canlı sunucuya geçince yapılacak)
- [ ] Canlı hosting kurulumu (SSL/HTTPS dahil — şu an sadece port 80)

## Lisans

MIT — bkz. [LICENSE](LICENSE)
