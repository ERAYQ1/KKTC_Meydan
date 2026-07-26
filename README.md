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

Detaylı içerik mimarisi, hashtag hiyerarşisi ve yapılacaklar listesi için bkz. [ROADMAP.md](ROADMAP.md).

## Özel Eklentiler

Bu repo'ya özel geliştirilen eklentiler `extensions/` klasöründe tutulur:

- **`extensions/business-profile`** — İşletme hesaplarına adres/telefon/WhatsApp/çalışma saati ekler, profilde herkese açık gösterir.
- **`extensions/report-status`** — Sorun Bildir konularına gerçek `report_status` alanı, renkli durum rozeti (🔴 bildirildi / 🟡 inceleniyor / 🔵 yetkiliye iletildi / 🟢 çözüldü) ve moderatör paneli dropdown'ı ekler.
- **`extensions/classifieds`** — İlan konularına yapılandırılmış `price`/`currency`/`location`/`contactPhone`/`classifiedType` alanları ekler (İlan Sistemi v2 altyapısı). Composer'da "Yaşam" veya ilan hashtag'lerinden biri seçilince fiyat/konum/iletişim formu belirir; liste ve detay sayfasında fiyat/konum rozeti gösterir.
- **`extensions/event-calendar`** — Etkinlik konularına yapılandırılmış `eventStartAt`/`eventEndAt` alanları ekler (Etkinlik Takvimi altyapısı). Composer'da "Keşfet" veya etkinlik hashtag'lerinden biri seçilince başlangıç/bitiş tarihi formu belirir; liste ve detay sayfasında `📅 DD.MM.YYYY HH:mm` rozeti gösterir.
- **`extensions/anonymous-posting`** — Yalnızca "Genel Meydan" kategorisinde 🕵️ anonim paylaşım. Telefon/doxxing regex filtresi (moderatör onay kuyruğuna düşürür), küfür/hakaret filtresi (gönderiyi reddeder), gerçek yazar bilgisi API seviyesinde herkesten gizlenir — sadece `discussion.viewIpsPosts` izni olan moderatörler `[Anonim] kullanıcı (IP: x.x.x.x)` biçiminde gerçek kimliği görebilir.

Derlenmiş JS (`js/dist/`) repo'ya dahildir, `node_modules/` değildir; JS kodunda değişiklik yaparsan ilgili eklentinin `js/` klasöründe `npm install && npm run build` çalıştırman gerekir.

## Teknoloji

- PHP 8.2 (motor: [Flarum](https://flarum.org) — özelleştirilmiş forum çekirdeği üzerine inşa edildi)
- MariaDB 10.11
- nginx
- Node.js (özel eklentilerin JS derlemesi için)
- Docker / Docker Compose

## Kurulum (tek komutla, Docker)

```bash
git clone https://github.com/ERAYQ1/KKTC_Meydan.git
cd KKTC_Meydan
cp config.example.php config.php
# config.php içindeki veritabanı bilgilerini düzenleyin (bu dosya .gitignore'da, asla commit etmeyin)
docker compose up -d --build
docker compose exec flarum-app composer install
```

Platform varsayılan olarak `http://localhost:8080` üzerinden erişilebilir olacaktır. `public/assets/` içindeki derlenmiş JS/CSS dosyaları git'e dahil değildir (`.gitignore`'da) — ilk istekte kendiliğinden derlenir; forum sayfasını ve admin panelini (giriş yaptıktan sonra) birer kez ziyaret etmek yeterlidir.

İlk kurulumdan sonra gerekli eklentileri etkinleştirin:

```bash
docker compose exec flarum-app php flarum extension:enable flarum-tags flarum-lang-turkish \
  flarum-subscriptions flarum-likes flarum-lock flarum-mentions flarum-markdown \
  flarum-bbcode flarum-emoji flarum-suspend flarum-sticky flarum-flags flarum-approval \
  kktcmeydan-business-profile kktcmeydan-report-status kktcmeydan-classifieds \
  kktcmeydan-event-calendar kktcmeydan-anonymous-posting fof-follow-tags \
  ianm-follow-users fof-sitemap fof-polls fof-oauth fof-gamification \
  fof-formatting fof-best-answer
```

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
- [x] Topluluk paketleri: en iyi cevap (`fof/best-answer`), anket (`fof/polls`), kullanıcı takibi (`ianm/follow-users`), Google OAuth altyapısı (`fof/oauth` — gerçek Client ID/Secret admin panelinden girilmeli), sitemap/SEO (`fof/sitemap`), YouTube/medya gömme (`fof/formatting`), rozet/itibar (`fof/gamification`) — hepsi kurulu, etkin, Türkçe çevirileri tamam
- [ ] Reklam sistemi (kategori/şehir/üniversite hedefleme)
- [ ] Mail/SMTP ayarı (kayıt onayı ve şifre sıfırlama şu an çalışmıyor — canlı sunucuya geçince yapılacak)
- [ ] Canlı hosting kurulumu

Detaylı durum ve teknik notlar için bkz. [ROADMAP.md](ROADMAP.md).

## Lisans

MIT — bkz. [LICENSE](LICENSE)
