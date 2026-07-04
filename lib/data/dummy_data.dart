// Dummy data for Lentera — transcribed from the design's data model so it can be
// swapped for the real API later. All content kept verbatim from the .dc.html.

import 'package:flutter/material.dart';
import '../theme/lentera_theme.dart';
import 'models.dart';

class LData {
  static const people = <Person>[
    Person(
        id: 1,
        name: 'Ibu',
        rel: 'Keluarga',
        initial: 'I',
        last: 'Masak bubur kesukaan waktu aku sakit',
        lastType: MomentType.positive,
        lastAgo: '2 jam',
        posCount: 24,
        negCount: 1,
        recall:
            'Sumber rasa amanmu. Tunjukkan apresiasi kecil hari ini — dia jarang minta. 💛'),
    Person(
        id: 2,
        name: 'Rian',
        rel: 'Sahabat',
        initial: 'R',
        last: 'Dengerin curhat sampai larut malam',
        lastType: MomentType.positive,
        lastAgo: 'Kemarin',
        posCount: 18,
        negCount: 3,
        recall:
            'Suportif tapi sensitif soal kerjaan. Hindari membahas gaji dulu ya.'),
    Person(
        id: 3,
        name: 'Pak Budi',
        rel: 'Atasan',
        initial: 'B',
        last: 'Memotong presentasiku di depan tim',
        lastType: MomentType.negative,
        lastAgo: 'Kemarin',
        posCount: 5,
        negCount: 7,
        recall:
            'Cenderung mengambil kredit di forum. Tetap tenang, simpan bukti tertulis, bicara empat mata bila perlu.'),
    Person(
        id: 4,
        name: 'Sarah',
        rel: 'Rekan kerja',
        initial: 'S',
        last: 'Bantu revisi laporan tanpa diminta',
        lastType: MomentType.positive,
        lastAgo: '2 hari',
        posCount: 9,
        negCount: 2,
        recall:
            'Andal & tulus. Balas kebaikannya — tawarkan bantuan duluan minggu ini. 🌷'),
    Person(
        id: 5,
        name: 'Dewi',
        rel: 'Tetangga',
        initial: 'D',
        last: 'Pinjam bor belum dikembalikan (2 minggu)',
        lastType: MomentType.neutral,
        lastAgo: '3 hari',
        posCount: 4,
        negCount: 1,
        recall:
            'Ramah tapi pelupa soal pinjaman. Ingatkan dengan santai saat ketemu.'),
  ];

  static const timelines = <int, List<TimelineEntry>>{
    1: [
      TimelineEntry(MomentType.positive, 'Hari ini · 21:30',
          'Masak bubur ayam kesukaanku waktu aku demam, padahal dia juga capek seharian.'),
      TimelineEntry(MomentType.neutral, '24 Jun',
          'Telepon nanya kabar, ngobrol 20 menit soal rencana lebaran.'),
      TimelineEntry(MomentType.positive, '18 Jun',
          'Diam-diam transfer uang waktu tahu dompetku menipis.',
          'Aku belum sempat bilang terima kasih langsung.'),
      TimelineEntry(MomentType.positive, '10 Jun',
          'Selalu menyimpan lauk favoritku setiap aku pulang telat.'),
    ],
    3: [
      TimelineEntry(MomentType.negative, 'Kemarin · 14:10',
          'Memotong presentasiku di tengah-tengah dan mengambil alih di depan seluruh tim.',
          'Aku merasa kecil. Sudah kusiapkan 3 hari.'),
      TimelineEntry(MomentType.neutral, '23 Jun',
          'Approve cuti tanpa banyak tanya. Netral, profesional.'),
      TimelineEntry(MomentType.negative, '15 Jun',
          'Mengklaim ideku soal efisiensi biaya sebagai gagasannya ke direktur.',
          'Pola berulang. Mulai simpan email & catatan.'),
      TimelineEntry(MomentType.positive, '2 Jun',
          'Sekali ini memuji kerjaku di chat grup. Jarang terjadi.'),
      TimelineEntry(MomentType.negative, '28 Mei',
          'Membalas pesan kerja jam 11 malam dan minta revisi mendadak untuk pagi.'),
    ],
  };

  static List<TimelineEntry> defaultTimeline(Person p) => [
        TimelineEntry(p.lastType, '${p.lastAgo} lalu', p.last),
        const TimelineEntry(MomentType.positive, 'Minggu lalu',
            'Momen kecil yang bikin harimu lebih ringan.'),
        const TimelineEntry(MomentType.neutral, '2 minggu lalu',
            'Interaksi biasa, dicatat untuk konteks.'),
      ];

  static const week = <WeekDay>[
    WeekDay('S', 2, 1),
    WeekDay('S', 3, 0),
    WeekDay('R', 1, 2),
    WeekDay('K', 4, 1),
    WeekDay('J', 2, 0),
    WeekDay('S', 3, 1),
    WeekDay('M', 2, 0),
  ];

  static const dayNames = [
    'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu' //
  ];

  static const homeFeed = <Moment>[
    Moment(
        id: 'f1',
        type: MomentType.positive,
        person: 'Ibu',
        personId: 1,
        text: 'Masak bubur kesukaanku waktu aku sakit.',
        ago: '2 jam'),
    Moment(
        id: 'f2',
        type: MomentType.negative,
        person: 'Pak Budi',
        personId: 3,
        text: 'Memotong presentasiku di depan tim.',
        ago: 'Kemarin'),
    Moment(
        id: 'f3',
        type: MomentType.positive,
        person: 'Rian',
        personId: 2,
        text: 'Nemenin curhat sampai larut, nggak ngeluh.',
        ago: 'Kemarin'),
    Moment(
        id: 'f4',
        type: MomentType.neutral,
        person: 'Dewi',
        personId: 5,
        text: 'Pinjam bor, katanya balikin minggu depan.',
        ago: '3 hari'),
  ];

  /// All-moments = homeFeed + these extras.
  static const allMomentsExtra = <Moment>[
    Moment(
        id: 'h5',
        type: MomentType.positive,
        person: 'Sarah',
        personId: 4,
        text: 'Bantu revisi laporanku tanpa diminta.',
        ago: '4 hari'),
    Moment(
        id: 'h6',
        type: MomentType.positive,
        person: 'Ibu',
        personId: 1,
        text: 'Menelepon hanya untuk memastikan aku sudah makan.',
        ago: '5 hari'),
    Moment(
        id: 'h7',
        type: MomentType.negative,
        person: 'Pak Budi',
        personId: 3,
        text: 'Mengklaim ideku soal efisiensi ke direktur.',
        ago: '1 minggu'),
    Moment(
        id: 'h8',
        type: MomentType.neutral,
        person: 'Rian',
        personId: 2,
        text: 'Ngajak ngopi tapi lupa konfirmasi jamnya.',
        ago: '1 minggu'),
    Moment(
        id: 'h9',
        type: MomentType.positive,
        person: 'Dewi',
        personId: 5,
        text: 'Bawakan makanan lebih saat masak banyak.',
        ago: '2 minggu'),
  ];

  /// Today's logged moments (auto-rekap source).
  static const todayBase = <Moment>[
    Moment(
        id: 't1',
        type: MomentType.positive,
        person: 'Ibu',
        personId: 1,
        text: 'Masak bubur kesukaanku waktu aku demam.',
        ago: 'hari ini'),
    Moment(
        id: 't2',
        type: MomentType.positive,
        person: 'Sarah',
        personId: 4,
        text: 'Bantu revisi laporanku tanpa kuminta.',
        ago: 'hari ini'),
    Moment(
        id: 't3',
        type: MomentType.negative,
        person: 'Pak Budi',
        personId: 3,
        text: 'Memotong presentasiku di depan tim.',
        ago: 'hari ini'),
  ];

  static final moods = <Mood>[
    Mood('Berat', const Color(0xFFE08A66), const Color(0xFFCD7A54),
        (c) => c.peachSoft),
    Mood('Kurang', const Color(0xFFE8B07A), const Color(0xFFC78A4E),
        (c) => const Color(0xFFFBF0E2)),
    Mood('Biasa', const Color(0xFFC9B8E0), const Color(0xFF7E72B8),
        (c) => c.lavSoft),
    Mood('Baik', const Color(0xFF8FD3AE), const Color(0xFF3F9D72),
        (c) => c.mintSoft),
    Mood('Cerah', const Color(0xFF5CB88A), const Color(0xFF2E8A5F),
        (c) => const Color(0xFFDCEFE4)),
  ];

  static const calHead = ['S', 'S', 'R', 'K', 'J', 'S', 'M'];

  static const prompts = <String>[
    'Kebaikan kecil apa yang kamu terima hari ini?',
    'Siapa yang membuat harimu sedikit lebih ringan?',
    'Hal apa yang patut kamu syukuri malam ini?',
    'Adakah seseorang yang ingin kamu apresiasi hari ini?',
  ];

  static const onboarding = <(String, String)>[
    (
      'Selamat datang di Lentera',
      'Ruang tenang untuk mensyukuri kebaikan dan menjaga batasanmu — cukup satu momen setiap hari.'
    ),
    (
      'Privasimu, mutlak',
      'Semua catatan dikunci (AES-256) langsung di perangkatmu. Bahkan kami pun tak bisa membacanya.'
    ),
    (
      'Buat kunci rahasia',
      'Passphrase ini yang membuka jurnalmu. Hanya kamu yang tahu — simpan baik-baik, jangan sampai lupa.'
    ),
  ];

  static const topics = ['Kerjaan', 'Keluarga', 'Pertemanan', 'Diri sendiri'];

  static const reactionDefs = <ReactionDef>[
    ReactionDef('peluk', '🫂', Color(0xFF7E72B8), Pal.lav),
    ReactionDef('kekuatan', '💪', Color(0xFF3F9D72), Pal.mint),
    ReactionDef('paham', '💛', Color(0xFFCD7A54), Pal.peach),
  ];

  static const circles = <Circle>[
    Circle('k1', 'Menjaga batas', '🛡️', '1,2rb', Pal.peach, Color(0xFFFAD3C0),
        'Tempat aman untuk belajar berkata "tidak" dan menjaga energimu.'),
    Circle('k2', 'Berdamai dengan keluarga', '🏡', '890', Pal.mint,
        Color(0xFFBFE6D0),
        'Ruang pelan untuk memperbaiki & menerima hubungan keluarga.'),
    Circle('k3', 'Pulih pelan-pelan', '🌱', '2,1rb', Pal.lav, Color(0xFFD6CCF2),
        'Langkah-langkah kecil menuju pulih — tanpa terburu-buru.'),
    Circle('k4', 'Syukur harian', '💛', '3,4rb', Pal.mint, Color(0xFFBFE6D0),
        'Merayakan kebaikan-kebaikan kecil setiap hari, bersama.'),
  ];

  static const community = <Post>[
    Post(
        id: 'c1',
        anon: true,
        author: 'Pejalan Senja',
        avatar: '🌙',
        avatarPal: Pal.lav,
        time: '5 mnt',
        text:
            'Hujan deras hari ini, dan aku bersyukur masih punya atap dan teh hangat. Hal kecil yang sering kulupa.',
        base: Reactions(34, 8, 21)),
    Post(
        id: 'c2',
        anon: false,
        author: 'Maya',
        avatar: 'M',
        avatarPal: Pal.mint,
        time: '18 mnt',
        text:
            'Akhirnya berani bilang "tidak" ke permintaan yang menguras. Bangga sama diriku yang pelan-pelan belajar menjaga batas. 🌱',
        base: Reactions(56, 41, 12),
        circle: 'Menjaga batas'),
    Post(
        id: 'c3',
        strength: true,
        anon: true,
        author: 'Seseorang',
        avatar: '🌧️',
        avatarPal: Pal.peach,
        time: '25 mnt',
        text:
            'Lagi berat banget. Capek rasanya melawan pikiran sendiri terus. Cuma mau cerita di sini, biar nggak kupendam sendiri.',
        base: Reactions.zero),
    Post(
        id: 'c4',
        anon: true,
        author: 'Daun Pagi',
        avatar: '🌿',
        avatarPal: Pal.mint,
        time: '1 jam',
        text:
            'Bersyukur untuk teman yang menelepon tanpa alasan — cuma ingin tahu aku baik-baik saja.',
        base: Reactions(88, 5, 33),
        circle: 'Syukur harian'),
  ];

  static const circleFeed = <String, List<Post>>{
    'k1': [
      Post(
          id: 'k1a',
          anon: true,
          author: 'Akar Tenang',
          avatar: '🌿',
          avatarPal: Pal.mint,
          time: '20 mnt',
          text:
              'Hari ini aku bilang "aku butuh waktu sendiri" tanpa merasa bersalah. Kecil, tapi besar buatku.',
          base: Reactions(22, 31, 9)),
      Post(
          id: 'k1b',
          anon: false,
          author: 'Tari',
          avatar: 'T',
          avatarPal: Pal.peach,
          time: '1 jam',
          text:
              'Menjaga batas bukan berarti tidak sayang. Justru supaya bisa sayang lebih lama.',
          base: Reactions(40, 18, 27)),
    ],
    'k2': [
      Post(
          id: 'k2a',
          anon: true,
          author: 'Pelangi Sore',
          avatar: '🌅',
          avatarPal: Pal.peach,
          time: '35 mnt',
          text:
              'Nelpon Bapak duluan setelah lama diam. Canggung, tapi lega rasanya.',
          base: Reactions(51, 12, 19)),
      Post(
          id: 'k2b',
          anon: true,
          author: 'Rumah Kayu',
          avatar: '🏡',
          avatarPal: Pal.mint,
          time: '2 jam',
          text:
              'Mencoba memahami Ibu dari sudut pandangnya. Pelan-pelan luka itu mengecil.',
          base: Reactions(33, 7, 44)),
    ],
    'k3': [
      Post(
          id: 'k3a',
          anon: true,
          author: 'Embun',
          avatar: '🌫️',
          avatarPal: Pal.lav,
          time: '15 mnt',
          text:
              'Hari ini aku bangun dan menyikat gigi. Untuk sebagian orang itu biasa, untukku itu kemenangan.',
          base: Reactions(120, 64, 38)),
      Post(
          id: 'k3b',
          anon: false,
          author: 'Nadia',
          avatar: 'N',
          avatarPal: Pal.mint,
          time: '3 jam',
          text: 'Pemulihan nggak selalu maju. Kadang mundur. Dan itu nggak apa-apa.',
          base: Reactions(88, 22, 51)),
    ],
    'k4': [
      Post(
          id: 'k4a',
          anon: true,
          author: 'Daun Pagi',
          avatar: '🌿',
          avatarPal: Pal.mint,
          time: '40 mnt',
          text: 'Bersyukur untuk matahari pagi yang masuk lewat jendela dapur.',
          base: Reactions(29, 3, 14)),
      Post(
          id: 'k4b',
          anon: true,
          author: 'Senja',
          avatar: '🌇',
          avatarPal: Pal.peach,
          time: '2 jam',
          text: 'Hari ini ada yang tersenyum ke aku di jalan. Hangat rasanya.',
          base: Reactions(47, 6, 22)),
    ],
  };

  static const promptAnswers = <Post>[
    Post(
        id: 'pa1',
        anon: true,
        author: 'Embun Pagi',
        avatar: '🌫️',
        avatarPal: Pal.lav,
        time: '3 mnt',
        text: 'Kopi pertama yang masih panas pagi ini.',
        base: Reactions(12, 2, 8)),
    Post(
        id: 'pa2',
        anon: true,
        author: 'Langit Biru',
        avatar: '☁️',
        avatarPal: Pal.mint,
        time: '11 mnt',
        text: 'Pesan singkat "hati-hati di jalan" dari Ibu.',
        base: Reactions(41, 3, 19)),
    Post(
        id: 'pa3',
        anon: true,
        author: 'Daun Jatuh',
        avatar: '🍂',
        avatarPal: Pal.peach,
        time: '22 mnt',
        text: 'Bisa tidur nyenyak semalam tanpa overthinking.',
        base: Reactions(33, 8, 27)),
    Post(
        id: 'pa4',
        anon: true,
        author: 'Ombak',
        avatar: '🌊',
        avatarPal: Pal.mint,
        time: '40 mnt',
        text: 'Badanku sehat hari ini. Sering kulupa mensyukuri itu.',
        base: Reactions(58, 11, 30)),
  ];

  static const struggles = <Struggle>[
    Struggle('st1', 'Seseorang', '🌧️', 'baru',
        'Lagi berat banget. Capek rasanya melawan pikiran sendiri terus.'),
    Struggle('st2', 'Langit Kelabu', '☁️', '12 mnt',
        'Ngerasa nggak pernah cukup baik di kerjaan. Sekeras apa pun usaha rasanya kurang.'),
    Struggle('st3', 'Pejalan Sunyi', '🌙', '30 mnt',
        'Capek pura-pura kuat di depan semua orang. Di sini boleh ya, jujur aja.'),
  ];

  static const strengthReplies = <QuickReply>[
    QuickReply('💛', 'Kamu nggak sendirian.'),
    QuickReply('🌱', 'Pelan-pelan, kamu sudah cukup.'),
    QuickReply('🫂', 'Aku kirim pelukan untukmu.'),
  ];

  static const reportReasons = <String>[
    'Ujaran menyakiti / kasar',
    'Spam / promosi',
    'Tidak pantas',
    'Pelecehan / perundungan',
    'Isyarat menyakiti diri',
    'Informasi salah',
  ];

  static const grounding = <GroundItem>[
    GroundItem('5', 'hal yang bisa kamu lihat', Pal.mint),
    GroundItem('4', 'hal yang bisa kamu sentuh', Pal.lav),
    GroundItem('3', 'hal yang bisa kamu dengar', Pal.peach),
    GroundItem('2', 'hal yang bisa kamu cium', Pal.mint),
    GroundItem('1', 'hal yang bisa kamu rasakan', Pal.lav),
  ];

  static const affirmations = <String>[
    'Perasaan ini nyata, tapi tidak akan selamanya.',
    'Kamu sudah bertahan sejauh ini. Itu bukan hal kecil.',
    'Tidak apa-apa untuk tidak baik-baik saja hari ini.',
    'Kamu layak ditemani, bukan dihakimi.',
    'Satu napas, lalu satu lagi. Itu sudah cukup.',
    'Besok adalah halaman baru yang belum ditulis.',
  ];

  /// Moderation: banned words (identical to console). Whole-word, case-insensitive.
  static final bannedWords = RegExp(
      r'\b(bodoh|tolol|goblok|sialan|brengsek|bangsat|benci\s*kamu)\b',
      caseSensitive: false);

  /// Gentle crisis / self-harm signal detection.
  static final crisisSignals = RegExp(
      r'\b(bunuh diri|mengakhiri hidup|akhiri hidup|tidak ingin hidup|ingin mati|pengen mati|menyerah saja|tak sanggup lagi|gak sanggup lagi|nggak sanggup lagi|menyakiti diri|lukai diri|capek hidup|lelah hidup|putus asa|hampa sekali)\b',
      caseSensitive: false);
}
