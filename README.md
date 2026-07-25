# KKTC Meydan

Kıbrıs'ın özgür tartışma ve topluluk meydanı.

KKTC'de yaşayan öğrencilerin, yerel halkın, çalışanların, işletmelerin ve ziyaretçilerin bilgi paylaşabileceği, soru sorabileceği, tartışabileceği ve yerel hizmetleri keşfedebileceği merkezi dijital topluluk platformu.

## Özellikler (planlanan ve mevcut)
- Bölge bazlı topluluklar (6 resmi ilçe: Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke + tüm alt yerleşimler)
- Üniversite toplulukları (KKTC'deki tüm üniversiteler — bkz. `site_settings.json`)
- Sorun Bildir (altyapı/belediye sorunları, durum takibi: bildirildi → inceleniyor → yetkiliye iletildi → çözüldü)
- İşletme profilleri (adres/telefon/WhatsApp/çalışma saati, hesaba bağlı)
- İlan sistemi (satılık/kiralık/iş ilanı/ev arkadaşı/ikinci el)
- Anonim ve hesaplı paylaşım
- Etkinlik paylaşımı
- Reklam sistemi (kategori/şehir/üniversite hedefleme)

Detaylı içerik mimarisi ve yapılacaklar listesi için bkz. [ROADMAP.md](ROADMAP.md).

## Teknoloji
- PHP 8.2
- MariaDB 10.11
- nginx
- Node.js (özel extension'ların JS derlemesi için)
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
  flarum-bbcode flarum-emoji flarum-suspend flarum-sticky flarum-flags flarum-approval \
  kktcmeydan-business-profile
```

Özel geliştirilen extension'lar `extensions/` klasöründe tutulur (bkz. `extensions/business-profile` — işletme hesaplarına adres/telefon/WhatsApp/çalışma saati ekler). Derlenmiş JS (`js/dist/`) repo'ya dahildir, `node_modules/` değildir; JS kodunda değişiklik yaparsan `extensions/<isim>/js` içinde `npm install && npm run build` çalıştırman gerekir.

Kategoriler (tag) ve site ayarları (isim, açıklama, tema rengi, hoş geldin mesajı) `site_settings.json` içinde tanımlıdır ve `seed.php` betiği ile otomatik uygulanır:

```bash
docker compose exec flarum-app php seed.php
```

Bu betik idempotenttir (tekrar tekrar çalıştırılabilir), `site_settings.json`'daki ayarları ve kategorileri uygular, ayrıca her kategoride örnek kullanıcı ve konu oluşturarak forumun boş görünmesini engeller. Admin şifresi gibi kimlik bilgileri hiçbir dosyada saklanmaz — kurulum sonrası admin panelinden değiştirin.

### Durum
- [x] Docker altyapısı (nginx + php-fpm + MariaDB + Node.js)
- [x] Flarum çekirdek kurulumu
- [x] Türkçe dil paketi etkin
- [x] 8 ana kategori: KKTC Gündem, Bölgeler, Üniversiteler, Ulaşım, Yaşam, Keşfet, Sorun Bildir, Genel Meydan
- [x] Seed betiği (site ayarları, kategoriler, örnek kullanıcı/konu)
- [x] Özel roller (Öğrenci, İşletme, Yerel Halk, Güvenilir Üye — şimdilik sadece rozet, ek izin yok, admin en yetkili grup olarak kalıyor)
- [x] Bölge/üniversite/konu hashtag'leri (ikincil etiket olarak, kategoriyle birlikte kullanılıyor) + gerçek KKTC konularıyla genişletilmiş örnek içerik
- [x] Sorun Bildir sistemi v1 (durum hashtag'leriyle)
- [x] İlan sistemi v1 (hashtag + standart format konvansiyonuyla)
- [x] İşletme dizini v1 (özel extension — bkz. `extensions/business-profile`)
- [ ] Reklam sistemi (kategori/şehir/üniversite hedefleme)
- [ ] Etkinlik takvimi
- [ ] Anonim paylaşım
- [ ] Mail/SMTP ayarı (kayıt onayı ve şifre sıfırlama şu an çalışmıyor — canlı sunucuya geçince yapılacak)
- [ ] Canlı hosting kurulumu

Detaylı durum ve teknik notlar için bkz. [ROADMAP.md](ROADMAP.md).

## Lisans
MIT — bkz. [LICENSE](LICENSE)
