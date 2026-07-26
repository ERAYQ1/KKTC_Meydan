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
- [ ] Kullanıcı takip etme / engelleme (mevcut extension ekosisteminde araştırılacak)
- [ ] Anket (poll) — mevcut extension ekosisteminde araştırılacak
- [ ] Video/YouTube embed — mevcut extension ekosisteminde araştırılacak
- [ ] SEO (sitemap, meta/OG, schema.org) — mevcut extension ekosisteminde araştırılacak
- [ ] Google ile giriş — mevcut extension ekosisteminde araştırılacak
- [ ] Rozet / itibar / seviye sistemi (gamification) — mevcut extension ekosisteminde araştırılacak
- [ ] En iyi cevap / çözüldü işaretleme — mevcut extension ekosisteminde araştırılacak

### Özel geliştirme gerektirir (Flarum'da hazır yok, extension yazılacak)

- [x] **Sorun Bildir sistemi — v1 (tag tabanlı)**: "Sorun Bildir" kategorisi + durum hashtag'leri (🔴 #bildirildi, 🟡 #inceleniyor, 🔵 #yetkiliye-iletildi, 🟢 #çözüldü, her biri renkli). Kullanıcı konum hashtag'i + durum hashtag'iyle bildirim açar, moderatör durumu değiştirmek için hashtag'i değiştirir (core tag-edit yetkisiyle, ek kod gerekmedi). 3 örnek konu farklı durumlarda eklendi.
- [x] **Node.js/npm Dockerfile'a eklendi** — custom Flarum extension geliştirmenin önündeki engel kalktı (v24.17.0 + npm 11.12.1)
- [x] **Sorun Bildir v2 (özel extension)**: `extensions/report-status/` — discussions tablosuna gerçek `report_status` sütunu (migration), `discussion.editReportStatus` izni (varsayılan moderatör grubu), renkli `ReportStatusBadge` (bildirildi kırmızı/inceleniyor sarı/yetkiliye-iletildi mavi/çözüldü yeşil), moderasyon kontrollerinde dropdown (durum seç/kaldır). v1'in tag tabanlı örnekleri (`#bildirildi` vb.) duruyor, yeni sistemle birlikte kullanılabilir — mevcut örnek konulara henüz `reportStatus` atanmadı (admin panelinden manuel işaretlenebilir).
- [x] **İşletme dizini — v1 (özel extension, ilk custom Flarum extension'ımız)**: `extensions/business-profile/` — kullanıcı hesabına adres/telefon/WhatsApp/çalışma saati eklenebiliyor (Ayarlar sayfasında form), profilde herkese açık gösteriliyor (UserCard'a eklendi). Doğrulanmış işletme rozeti zaten var olan "İşletme" rolüyle karşılanıyor (admin manuel atar). Örnek işletme kullanıcısı (can_maguza) gerçek iletişim bilgisiyle seed edildi.
  - Teknik not: Flarum'un `preferences` alanı sadece hesap sahibine özel (CurrentUserSerializer) — işletme bilgisi herkese görünür olmalı, bu yüzden `Extend\ApiSerializer(UserSerializer::class)` ile ayrı public attribute olarak eklendi.
  - Kalan: harita entegrasyonu, fotoğraf, yorum/puan sistemi — v2.
- [x] **Etkinlik takvimi — v1 altyapısı (özel extension)**: `extensions/event-calendar/` — discussions tablosuna `event_start_at`/`event_end_at` (datetime, nullable) sütunları (migration), `DiscussionSerializer`'a `eventStartAt`/`eventEndAt` attribute'ları, `Saving` event listener (geçersiz tarih string'i sessizce null'a düşüyor, bitiş başlangıçtan önceyse bitiş tarihi düşürülüyor). Composer'da "Keşfet" veya etkinlik hashtag'lerinden biri (`etkinlik`/`konser`/`festival`/`workshop`, `site_settings.json`'a eklendi) seçilince başlangıç/bitiş `datetime-local` inputu beliriyor; liste ve detay sayfasında (`Discussion.badges()`) 📅 `DD.MM.YYYY HH:mm` rozeti gösteriliyor. Backend PHP script ile create/edit/geçersiz-tarih/ters-aralık senaryoları doğrulandı.
  - Kalan (v2): gerçek takvim görünümü (aylık/haftalık grid sayfası), etkinliğe katılım/RSVP, hatırlatma bildirimi.
- [ ] **Anonim paylaşım** — kullanıcı hesabıyla ya da anonim paylaşabilir; moderatör gerçek kimliği görebilir, normal kullanıcı göremez; IP/log güvenli tutulur; taciz/tehdit içeriği için moderasyon uygulanır. Gizlilik/hukuki risk taşıdığı için dikkatli tasarlanacak.
- [x] **İlan sistemi — v1 (konvansiyon + hashtag)**: "Yaşam" kategorisi altında #satılık/#kiralık/#iş-ilanı/#ev-arkadaşı/#ikinci-el hashtag'i + standart başlık formatı (📍 Konum, 💰 Fiyat, 📞 İletişim, 📅 Tarih). 4 örnek ilan eklendi (kiralık daire, satılık daire, iş ilanı, ev arkadaşı). Kategori açıklamasında format kullanıcıya gösteriliyor.
- [x] **İlan sistemi v2 (özel extension)**: `extensions/classifieds/` — discussions tablosuna `price` (decimal), `currency` (varchar, varsayılan TRY), `location`, `contact_phone`, `classified_type` sütunları (migration), `DiscussionSerializer`'a `price`/`currency`/`location`/`contactPhone`/`classifiedType` attribute'ları, `Saving` event listener (geçersiz para birimi/ilan türü sessizce reddedilip varsayılana düşüyor). Composer'da "Yaşam" veya ilan hashtag'lerinden biri seçilince fiyat/para birimi/konum/telefon/ilan-türü formu beliriyor (`DiscussionComposer` headerItems + data extend), liste ve detay sayfasında (`Discussion.badges()` ile) fiyat ve konum rozeti gösteriliyor. Backend PHP script ile create/edit/geçersiz-değer senaryoları doğrulandı.
  - Kalan (v3): fiyata göre filtreleme/sıralama, ayrı ilan listesi görünümü, mevcut konuyu düzenlerken (composer dışında, discussion sayfasında) alanları değiştirme UI'ı. Reklam sistemi (kategori/şehir/üniversite hedefleme) bu altyapının üzerine kurulacak.
- [ ] Detaylı istatistik/analitik admin paneli (günlük/haftalık aktif kullanıcı, en aktif kategori)
- [ ] Otomatik moderasyon / kelime filtresi (özellikle Sağlık ve Güvenlik kategorilerinde kişisel veri/suçlama riskine karşı)

### Zaten var (Flarum core + etkin extension'lar)

Kayıt/giriş, profil, avatar, bio, e-posta bildirimi, zengin metin editörü + markdown/bbcode, kod bloğu, link/görsel/dosya ekleme, alıntı, mention (@kullanıcı), tag, sabitleme/kilitleme, raporlama (flags), beğeni (likes), takip (subscriptions), yeni üye ilk mesaj onayı (approval), susturma/ban (suspend), temel arama, temel admin paneli (kullanıcı/kategori/tag/rol/tema/site ayarı), mobil uyumlu tasarım.

## Sıra

Bu liste sırayla, tek tek uygulanacak — kullanıcı onayı ile ilerlenecek, atlama yapılmayacak.
