export type MeasurementSystem = 'imperial' | 'metric'
export type MeasurementDimension = 'weight' | 'distance'

const UNIT_LABELS: Record<MeasurementSystem, Record<MeasurementDimension, string>> = {
  imperial: { weight: 'lb', distance: 'mi' },
  metric: { weight: 'kg', distance: 'km' },
}

export function unitLabel(system: MeasurementSystem, dimension: MeasurementDimension): string {
  return UNIT_LABELS[system][dimension]
}
