export type MeasurementSystem = 'imperial' | 'metric'
export type MeasurementDimension = 'weight' | 'distance' | 'speed'

const UNIT_LABELS: Record<MeasurementSystem, Record<MeasurementDimension, string>> = {
  imperial: { weight: 'lb', distance: 'mi', speed: 'mph' },
  metric: { weight: 'kg', distance: 'km', speed: 'km/h' },
}

export function unitLabel(system: MeasurementSystem, dimension: MeasurementDimension): string {
  return UNIT_LABELS[system][dimension]
}
