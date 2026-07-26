# KKTC Meydan — Yol Haritası

Bu dosya projenin uzun vadeli içerik mimarisi ve özellik planını tutar. Sırayla, eksiksiz uygulanacak. Her madde bitince işaretlenir.

## Kategori / Etiket Mimarisi (hedef yapı)

Kritik tasarım kararı: **kategoriler admin tarafından sabit tutulur, kullanıcılar sadece etiket (tag) ekler.** Kullanıcıya serbest kategori açma izni verilmez — aksi halde "Girne", "Girne Bölgesi", "Kyrenia" gibi yüzlerce yinelenen kategori oluşur.

### Ana kategoriler (primary tag, 8 giriş noktası)

1. 🏛️ KKTC Gündem — haberler, kamu hizmetleri, resmi duyurular, yasalar, ekonomi, siyaset, eğitim/sağlık gündemi, trafik, elektrik, su, internet
2. 📍 Bölgeler — Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke (+ alt yerleşimler child tag olarak)
3. 🎓 Üniversiteler — üniversite tercihleri, bölümler, kampüs, sınav, burs, yatay/dikey geçiş, Erasmus, yurt, ev arkadaşı, öğrenci indirimi (her üniversite kendi child tag'i)
4. 🚌 Ulaşım — otobüs (saat/güzergah/durak), taksi/araç paylaşımı, araç alım-satım-kiralama, trafik, kaza/tehlike bildirimi, ehliyet, park
5. 🏠 Yaşam (Ev/Yurt/İş) — kiralık/satılık ev, öğrenci evi, ev/oda arkadaşı, iş ilanı, staj, freelance, kariyer, ikinci el eşya
6. 🍔 Keşfet (Mekan/Etkinlik/Gezi) — restoran/kafe/bar, etkinlik (konser/festival/workshop), gezi/turizm/plaj/tarihi yer
7. 🛠️ Sorun Bildir — altyapı/belediye sorunları, statü takibi ile (bkz. özel geliştirme bölümü)
8. 🗣️ Meydan — genel sohbet, mizah, itiraf, tartışma, anonim paylaşım

Alt seviyede kalabilecek ek kategoriler: Teknoloji, Spor, Hayvanlar, Güvenlik/Acil Durum, Kamu ve Vatandaş (hukuki deneyim paylaşımı, tavsiye değil), Off-Topic.

### Hashtag katmanları (secondary tag, kategoriden bağımsız)

- **Konum**: Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke + alt yerleşimler (child tag olarak parent şehre bağlı — Flarum tek seviye nesting destekliyor, "ilçe → bölge → konu" 3 seviyesi "şehir tag + alt-yerleşim child tag" olarak 2 seviyeye indirilecek)
- **Üniversite**: mevcut 23 üniversite (zaten uygulandı, bkz. `site_settings.json`)
- **Konu**: #otobüs #taksi #ev #yurt #iş #staj #burs #elektrik #su #internet
- **Durum**: #çözüldü #acil #güncel #duyuru #tartışma
- **İlan tipi**: #satılık #kiralık #iş-ilanı #staj #ev-arkadaşı #ikinci-el

## Durum / Yapılacaklar

### Flarum'un mevcut özellikleriyle doğrudan yapılabilir (hızlı, ek kod gerektirmez)

- [x] Temel kategori + hashtag altyapısı (6 kategori, 23 üniversite hashtag'i) — bkz. `seed.php`, `site_settings.json`
- [x] 8 ana kategoriye geçiş (KKTC Gündem, Bölgeler, Üniversiteler, Ulaşım, Yaşam, Keşfet, Sorun Bildir, Genel Meydan — emlak/ikinci-el eski kategorileri Yaşam altında birleştirildi, discussion'lar otomatik taşındı)
- [x] Konum hashtag hiyerarşisi (6 resmi ilçe — Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke — + Bölgeler'e bağlı, altlarında tüm alt yerleşimler child tag olarak)
- [x] Konu/durum/ilan-tipi hashtag setleri (otobüs/taksi/ev/yurt/iş/staj/burs/internet, çözüldü/acil/güncel/duyuru/tartışma, satılık/kiralık/iş-ilanı/ev-arkadaşı/ikinci-el)
- [x] Kullanıcı takip etme (`ianm/follow-users` + bağımlılığı `fof/follow-tags`) — kuruldu, etkinleştirildi, Türkçe çeviri tamamlandı. Not: "engelleme" (blocking) bu paket kapsamında değil, sadece takip; engelleme ayrı bir extension gerektirir, henüz araştırılmadı.
- [x] Anket (poll) — `fof/polls` kuruldu, etkinleştirildi, Türkçe çeviri tamamlandı
- [x] Video/YouTube embed + zengin medya kartları — `fof/formatting` kuruldu, etkinleştirildi (Autovideo/Autoimage/MediaEmbed alt eklentileri dahil), Türkçe çeviri tamamlandı
- [x] SEO & otomatik sitemap — `fof/sitemap` kuruldu, etkinleştirildi, `/sitemap.xml` canlı doğrulandı, Türkçe çeviri tamamlandı. Meta/OG/schema.org etiketleri bu paketin kapsamında değil, ayrı bir SEO extension'ı gerektirir — henüz yapılmadı.
- [x] Google ile giriş (altyapı) — `fof/oauth` kuruldu, etkinleştirildi, Türkçe çeviri tamamlandı. **Admin panelinden gerçek Google OAuth Client ID/Secret girilmeden çalışmaz** — bunlar Google Cloud Console'da oluşturulup admin panelinde (`fof-oauth` ayarları) girilmeli, bu bilgiler dışarıdan sağlanamadığı için kod tarafı hazır, devreye alma admin'e kalıyor.
- [x] Rozet / itibar / seviye sistemi (gamification) — `fof/gamification` kuruldu, etkinleştirildi, Türkçe çeviri tamamlandı (olumlu/olumsuz oy, puan, rütbe, sıralama sayfası)
- [x] En iyi cevap / çözüldü işaretleme — `fof/best-answer` kuruldu, etkinleştirildi, Türkçe çeviri tamamlandı. Not: hangi etiketlerde aktif olacağı admin panelinden (Ayarlar → FoF Best Answer) seçilmeli, varsayılan olarak hiçbir etikette açık değil.

### Özel geliştirme gerektirir (Flarum'da hazır yok, extension yazılacak)

- [x] **Sorun Bildir sistemi — v1 (tag tabanlı)**: "Sorun Bildir" kategorisi + durum hashtag'leri (🔴 #bildirildi, 🟡 #inceleniyor, 🔵 #yetkiliye-iletildi, 🟢 #çözüldü, her biri renkli). Kullanıcı konum hashtag'i + durum hashtag'iyle bildirim açar, moderatör durumu değiştirmek için hashtag'i değiştirir (core tag-edit yetkisiyle, ek kod gerekmedi). 3 örnek konu farklı durumlarda eklendi.
- [x] **Node.js/npm Dockerfile'a eklendi** — custom Flarum extension geliştirmenin önündeki engel kalktı (v24.17.0 + npm 11.12.1)
- [x] **Sorun Bildir v2 (özel extension)**: `extensions/report-status/` — discussions tablosuna gerçek `report_status` sütunu (migration), `discussion.editReportStatus` izni (varsayılan moderatör grubu), renkli `ReportStatusBadge` (bildirildi kırmızı/inceleniyor sarı/yetkiliye-iletildi mavi/çözüldü yeşil), moderasyon kontrollerinde dropdown (durum seç/kaldır). v1'in tag tabanlı örnekleri (`#bildirildi` vb.) duruyor, yeni sistemle birlikte kullanılabilir — mevcut örnek konulara henüz `reportStatus` atanmadı (admin panelinden manuel işaretlenebilir).
- [x] **İşletme dizini — v1 (özel extension, ilk custom Flarum extension'ımız)**: `extensions/business-profile/` — kullanıcı hesabına adres/telefon/WhatsApp/çalışma saati eklenebiliyor (Ayarlar sayfasında form), profilde herkese açık gösteriliyor (UserCard'a eklendi). Doğrulanmış işletme rozeti zaten var olan "İşletme" rolüyle karşılanıyor (admin manuel atar). Örnek işletme kullanıcısı (can_maguza) gerçek iletişim bilgisiyle seed edildi.
  - Teknik not: Flarum'un `preferences` alanı sadece hesap sahibine özel (CurrentUserSerializer) — işletme bilgisi herkese görünür olmalı, bu yüzden `Extend\ApiSerializer(UserSerializer::class)` ile ayrı public attribute olarak eklendi.
  - Kalan: harita entegrasyonu, fotoğraf, yorum/puan sistemi — v2.
- [x] **Etkinlik takvimi — v1 altyapısı (özel extension)**: `extensions/event-calendar/` — discussions tablosuna `event_start_at`/`event_end_at` (datetime, nullable) sütunları (migration), `DiscussionSerializer`'a `eventStartAt`/`eventEndAt` attribute'ları, `Saving` event listener (geçersiz tarih string'i sessizce null'a düşüyor, bitiş başlangıçtan önceyse bitiş tarihi düşürülüyor). Composer'da "Keşfet" veya etkinlik hashtag'lerinden biri (`etkinlik`/`konser`/`festival`/`workshop`, `site_settings.json`'a eklendi) seçilince başlangıç/bitiş `datetime-local` inputu beliriyor; liste ve detay sayfasında (`Discussion.badges()`) 📅 `DD.MM.YYYY HH:mm` rozeti gösteriliyor. Backend PHP script ile create/edit/geçersiz-tarih/ters-aralık senaryoları doğrulandı.
  - Kalan (v2): gerçek takvim görünümü (aylık/haftalık grid sayfası), etkinliğe katılım/RSVP, hatırlatma bildirimi.
- [x] **Anonim paylaşım — v1 (özel extension)**: `extensions/anonymous-posting/` — discussions/posts tablolarına `is_anonymous` (boolean) sütunları (2 migration). Sadece "Genel Meydan" (`serbest`) kategorisinde aktif; başka kategoride `isAnonymous:true` gönderilirse sunucu sessizce false'a düşürüyor (hem discussion hem post seviyesinde, iki ayrı `Saving` listener). İçerik filtresi (`ContentFilter`): telefon/doxxing regex'i (KKTC 0392 alan kodu + 05xx/mobil önekleri, ayraçlardan bağımsız) tespit edilirse gönderi reddedilmiyor, `is_approved=false` ile moderatör onay kuyruğuna düşüyor (post + ilk mesajsa discussion, ikinci bir `save()` ile deterministik override — flarum/approval'ın kendi `Saving` listener'ıyla sıralama çakışmasın diye); küfür/hakaret listesi eşleşirse `ValidationException` ile doğrudan reddediliyor ("İçerik topluluk kurallarına aykırı ifadeler içermektedir"). Kimlik maskeleme sunucu taraflı: `AnonymousMasker` sınıfı 8 API controller'da (`ListPosts`/`ShowPost`/`CreatePost`/`UpdatePost`/`ListDiscussions`/`ShowDiscussion`/`CreateDiscussion`/`UpdateDiscussion`) `prepareDataForSerialization` ile anonim içeriklerin `user` ilişkisini HERKES için null'luyor (API yanıtında gerçek kullanıcı hiç görünmüyor — sadece frontend'de gizlemek yetmez, doğrulandı); `anonymousModLabel` attribute'u sadece `discussion.viewIpsPosts` iznine sahip moderatör/adminlere `[Anonim] kullanıcı (IP: x.x.x.x)` biçiminde gerçek kimliği açıyor (core'un hazır izniyle, yeni izin eklemeye gerek kalmadı). Composer'da (hem yeni konu hem yanıt) "Genel Meydan" seçiliyken 🕵️ checkbox beliriyor; `PostUser` ve `DiscussionListItem` yazar görünümü anonim gönderilerde "Anonim Üye" + gizli-ajan ikonuyla override ediliyor (`override()` ile tam view replace). Backend PHP script ile temiz-içerik/yanlış-kategori/küfür-red/telefon-kuyruk/izin-gate/relation-mask senaryoları + gerçek HTTP `/api/discussions` ve `/api/posts` çağrısıyla guest görünümünde gerçek kullanıcı verisinin hiç dönmediği doğrulandı.
  - Kalan (v2): discussion listesinde `?include=firstPost` ile gelen ilk mesaj önizlemesinin maskelenmesi (şu an projede bu içerik önizleme özelliği aktif kullanılmıyor, düşük öncelik), taciz/tehdit bildirimi için ayrı bir raporlama akışı, IP/log saklama süresi politikası.
- [x] **İlan sistemi — v1 (konvansiyon + hashtag)**: "Yaşam" kategorisi altında #satılık/#kiralık/#iş-ilanı/#ev-arkadaşı/#ikinci-el hashtag'i + standart başlık formatı (📍 Konum, 💰 Fiyat, 📞 İletişim, 📅 Tarih). 4 örnek ilan eklendi (kiralık daire, satılık daire, iş ilanı, ev arkadaşı). Kategori açıklamasında format kullanıcıya gösteriliyor.
- [x] **İlan sistemi v2 (özel extension)**: `extensions/classifieds/` — discussions tablosuna `price` (decimal), `currency` (varchar, varsayılan TRY), `location`, `contact_phone`, `classified_type` sütunları (migration), `DiscussionSerializer`'a `price`/`currency`/`location`/`contactPhone`/`classifiedType` attribute'ları, `Saving` event listener (geçersiz para birimi/ilan türü sessizce reddedilip varsayılana düşüyor). Composer'da "Yaşam" veya ilan hashtag'lerinden biri seçilince fiyat/para birimi/konum/telefon/ilan-türü formu beliriyor (`DiscussionComposer` headerItems + data extend), liste ve detay sayfasında (`Discussion.badges()` ile) fiyat ve konum rozeti gösteriliyor. Backend PHP script ile create/edit/geçersiz-değer senaryoları doğrulandı.
  - Kalan (v3): fiyata göre filtreleme/sıralama, ayrı ilan listesi görünümü, mevcut konuyu düzenlerken (composer dışında, discussion sayfasında) alanları değiştirme UI'ı.
- [x] **Reklam sistemi (özel extension)**: `extensions/ads-manager/` — `ads` tablosu (title, image_url, target_url, target_category_slug, target_district_slug, target_university_slug, position, is_active, impressions_count, clicks_count). Admin panelinde tam CRUD sayfası (`ExtensionPage` + ekle/düzenle modalı, aktif/pasif, hedef kategori/bölge/üniversite slug girişi — görsel dosya yükleme değil URL girişi, MVP kapsamında). Forumda `IndexPage.contentItems()` üzerinden konu listesinin en üstünde, o an görüntülenen etikete (kategori/bölge/üniversite) hedeflenmiş reklam varsa `AdBanner` gösteriliyor; hedefsiz reklamlar sadece genel (etiketsiz) listede görünüyor. Gösterim ve tıklama sayaçları gerçek zamanlı API çağrısıyla (`/ads/{id}/impression`, `/ads/{id}/click`) artıyor. Uçtan uca doğrulandı: hedefleme filtresi, sayaç artışı, guest CSRF akışı, admin-only oluşturma/silme (guest 403).
  - Kalan (v2): gerçek görsel dosya yükleme (şu an URL), tarih aralığı/bütçe bazlı kampanya yönetimi, çoklu banner konumu (şu an tek `position`).
- [x] **Detaylı istatistik/analitik admin paneli (özel extension)**: `extensions/analytics-dashboard/` — migration yok (mevcut tablolar üzerinden salt-okunur agregasyon). `/api/analytics/summary` (admin-only) toplam konu/mesaj/üye, DAU (`last_seen_at` bugün), WAU (son 7 gün), en çok konu açılan ve en çok yanıt alan 8 kategori (etiket bazında `discussion_count` ve `SUM(comment_count)`). Admin panelinde kart + çubuk grafik görünümüyle `ExtensionPage`. Gerçek veriyle doğrulandı (19 konu/38 mesaj/8 üye/2 DAU üzerinden).
- [x] **Otomatik moderasyon / Sağlık-Güvenlik-Kamu kalkanı (özel extension)**: `extensions/auto-moderation/` — migration yok, saf backend dinleyici. `Sağlık`, `Güvenlik / Acil Durum`, `Kamu` etiketleri (`site_settings.json`'a yeni ikincil hashtag olarak eklendi) altındaki konu/yanıtlarda: (1) 11 haneli kimlik numarası kalıbı (TC/KKTC), (2) sağlık mahremiyeti ihlali kelime listesi (HIV/kanser/psikiyatri vb. teşhis paylaşımı), (3) kurum/meslek hedefli karalama (doktor/hastane/belediye/polis vb. + rüşvet/sahtekarlık/dolandırıcılık vb. suçlama kelimelerinin AYNI mesajda birlikte geçmesi) tespit ediliyor. Eşleşen içerik `is_approved=false` ile moderatör onay kuyruğuna düşüyor (ilk mesajsa konu da); kimlik numarası tespiti VEYA "Güvenlik / Acil Durum" etiketi durumunda ek olarak `flarum-flags` tablosuna otomatik rapor (`type: kktcmeydan-auto-moderation`) düşüyor. Düzenlenmiş kategoriler dışında (ör. KKTC Gündem) aynı içerik tamamen dokunulmadan geçiyor — 6 senaryoyla (temiz/kimlik-no/sağlık-mahremiyeti/karalama/güvenlik-bayraklı/kapsam-dışı) uçtan uca doğrulandı.

### Zaten var (Flarum core + etkin extension'lar)

Kayıt/giriş, profil, avatar, bio, e-posta bildirimi, zengin metin editörü + markdown/bbcode, kod bloğu, link/görsel/dosya ekleme, alıntı, mention (@kullanıcı), tag, sabitleme/kilitleme, raporlama (flags), beğeni (likes), takip (subscriptions), yeni üye ilk mesaj onayı (approval), susturma/ban (suspend), temel arama, temel admin paneli (kullanıcı/kategori/tag/rol/tema/site ayarı), mobil uyumlu tasarım.

## Sıra

Bu liste sırayla, tek tek uygulanacak — kullanıcı onayı ile ilerlenecek, atlama yapılmayacak.
