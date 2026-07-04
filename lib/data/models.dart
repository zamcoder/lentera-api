// Data models for Lentera. Dummy data only for now — see dummy_data.dart.
// Kept deliberately plain so they map cleanly onto the Laravel API later.

import 'package:flutter/material.dart';
import '../theme/lentera_theme.dart';

/// Soft-palette family for avatars / chips (mint · peach · lavender).
enum Pal { mint, peach, lav }

extension PalX on Pal {
  Color soft(LenteraColors c) => switch (this) {
        Pal.mint => c.mintSoft,
        Pal.peach => c.peachSoft,
        Pal.lav => c.lavSoft,
      };
  Color get deep => switch (this) {
        Pal.mint => const Color(0xFF3F9D72),
        Pal.peach => const Color(0xFFCD7A54),
        Pal.lav => const Color(0xFF7E72B8),
      };

  static Pal fromType(MomentType t) => switch (t) {
        MomentType.positive => Pal.mint,
        MomentType.negative => Pal.peach,
        MomentType.neutral => Pal.lav,
      };
}

class Person {
  final int id;
  final String name, rel, initial, last, lastAgo, recall;
  final MomentType lastType;
  final int posCount, negCount;

  const Person({
    required this.id,
    required this.name,
    required this.rel,
    required this.initial,
    required this.last,
    required this.lastType,
    required this.lastAgo,
    required this.posCount,
    required this.negCount,
    required this.recall,
  });
}

/// A logged moment (home feed / all-moments / today).
class Moment {
  final String id;
  final MomentType type;
  final String person, text, ago;
  final int? personId;

  const Moment({
    required this.id,
    required this.type,
    required this.person,
    required this.text,
    required this.ago,
    this.personId,
  });
}

class TimelineEntry {
  final MomentType type;
  final String date, text;
  final String? context;
  const TimelineEntry(this.type, this.date, this.text, [this.context]);
}

class WeekDay {
  final String label;
  final int pos, neg;
  const WeekDay(this.label, this.pos, this.neg);
}

class Mood {
  final String label;
  final Color dot, deep;
  final Color Function(LenteraColors) soft;
  const Mood(this.label, this.dot, this.deep, this.soft);
}

/// Base reaction counts on a community post.
class Reactions {
  final int peluk, kekuatan, paham;
  const Reactions(this.peluk, this.kekuatan, this.paham);
  static const zero = Reactions(0, 0, 0);

  int byKind(String k) => switch (k) {
        'peluk' => peluk,
        'kekuatan' => kekuatan,
        'paham' => paham,
        _ => 0,
      };
}

/// A community / circle / prompt post (seed data — immutable).
class Post {
  final String id;
  final bool anon;
  final String author, avatar, time, text;
  final Pal avatarPal;
  final Reactions base;
  final String? circle;
  final bool strength;

  const Post({
    required this.id,
    required this.anon,
    required this.author,
    required this.avatar,
    required this.avatarPal,
    required this.time,
    required this.text,
    required this.base,
    this.circle,
    this.strength = false,
  });
}

/// A post authored by the current user this session (mutable moderation state).
class MyPost {
  final String id;
  final bool anon;
  final String text;
  final String? circle;
  bool pending;
  bool approved;
  MyPost({
    required this.id,
    required this.anon,
    required this.text,
    this.circle,
    this.pending = true,
    this.approved = false,
  });
}

class Circle {
  final String id, name, emoji, members, desc;
  final Pal pal;
  final Color blob;
  const Circle(this.id, this.name, this.emoji, this.members, this.pal, this.blob,
      this.desc);
}

class Struggle {
  final String id, author, avatar, time, text;
  const Struggle(this.id, this.author, this.avatar, this.time, this.text);
}

class ReactionDef {
  final String kind, emoji;
  final Color deep;
  final Pal pal;
  const ReactionDef(this.kind, this.emoji, this.deep, this.pal);
}

class QuickReply {
  final String emoji, text;
  const QuickReply(this.emoji, this.text);
}

class GroundItem {
  final String n, text;
  final Pal pal;
  const GroundItem(this.n, this.text, this.pal);
}
