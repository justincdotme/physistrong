export function collectReorderExerciseIds(
  blocks: Array<{ entries: Array<{ exerciseId: string }> }>,
  attached: Array<{ id: string }>
): string[] {
  const ordered = blocks.flatMap(b => b.entries.map(e => e.exerciseId))
  // Attached exercises whose sets were all removed render no block but still
  // count toward the API's completeness rule; keep them at the tail.
  attached.forEach(ex => ordered.push(ex.id))
  return [...new Set(ordered)]
}
