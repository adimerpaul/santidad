import '../lib/ad_sync.dart';

void main() {
  final state = <String, dynamic>{
    'ready': true,
    'epoch_ms': 1000,
    'items': [
      {'duration_ms': 10000},
      {'duration_ms': 30000},
      {'duration_ms': 110000},
    ],
  };
  assert(adPosition(state, 999) == null);
  assert(adPosition(state, null) == null);
  final late = adPosition(state, 26000)!;
  assert(
    late.index == 1 && late.offsetMs == 15000 && late.remainingMs == 15000,
  );
  final next = adPosition(state, 41000)!;
  assert(next.index == 2 && next.offsetMs == 0);
  assert(adPosition(state, 151000)!.index == 0);
  assert(adPosition({...state, 'ready': false}, 30000) == null);
  final clock = AdServerClock();
  clock.sample(50000, clock.tick, clock.tick);
  assert((clock.time! - 50000).abs() < 100);
  clock.sample(999999, -10000, clock.tick);
  assert((clock.time! - 50000).abs() < 100);
  print(
    'Android: future activation, late join, durations and clock checks passed',
  );
}
