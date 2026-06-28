import {
  LineChart as RechartsLineChart,
  Line,
  BarChart as RechartsBarChart,
  Bar,
  XAxis,
  YAxis,
  ResponsiveContainer,
  CartesianGrid,
  Tooltip,
  Cell,
} from 'recharts'

const COLORS = {
  primary: '#0D9488',
  accent: '#EA580C',
  border: 'var(--color-border)',
  textMuted: 'var(--color-text-muted)',
  textSecondary: 'var(--color-text-secondary)',
}

interface ProgressPoint {
  date: string
  value: number
  reps: number | null
  entryId: string
  isPR: boolean
}

interface ProgressLineChartProps {
  points: ProgressPoint[]
  unit: string
  height?: number
  valueFormatter?: (value: number) => string
}

interface VolumeBarChartProps {
  data: { date: string; value: number }[]
  height?: number
  valueFormatter?: (value: number) => string
}

function formatDateShort(dateString: string): string {
  const date = new Date(dateString)
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${month}/${day}`
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function CustomTooltip({
  active,
  payload,
  unit,
  valueFormatter,
}: {
  active?: boolean
  payload?: Array<{ value: number; payload: ProgressPoint }>
  unit: string
  valueFormatter: (value: number) => string
}) {
  if (!active || !payload || payload.length === 0) return null

  const data = payload[0]?.payload
  if (!data) return null
  const fmt = valueFormatter(data.value)

  return (
    <div
      className="ps-card px-3 py-2 pointer-events-none"
      style={{ boxShadow: '0 6px 20px rgba(0,0,0,.16)' }}
    >
      <div className="text-[11px] text-text-secondary whitespace-nowrap">
        {formatDate(data.date)}
      </div>
      <div
        className="font-bold whitespace-nowrap"
        style={{ fontSize: 18, letterSpacing: '-0.5px' }}
      >
        {fmt}
        {unit && <span className="text-text-secondary text-xs font-medium ml-1">{unit}</span>}
        {data.reps != null && (
          <span className="text-text-secondary text-xs font-medium ml-1">× {data.reps}</span>
        )}
      </div>
      {data.isPR && <div className="text-[11px] font-bold text-accent">New PR</div>}
    </div>
  )
}

export function ProgressLineChart({
  points,
  unit,
  height = 220,
  valueFormatter,
}: ProgressLineChartProps) {
  const fmt = valueFormatter || ((v: number) => Math.round(v).toString())

  if (!points.length) {
    return (
      <div className="text-text-muted text-sm py-10 text-center">No data in this range yet.</div>
    )
  }

  return (
    <div className="w-full">
      <ResponsiveContainer width="100%" height={height}>
        <RechartsLineChart data={points} margin={{ top: 16, right: 16, bottom: 28, left: 40 }}>
          <defs>
            <linearGradient id="ps-area" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={COLORS.primary} stopOpacity="0.16" />
              <stop offset="100%" stopColor={COLORS.primary} stopOpacity="0" />
            </linearGradient>
          </defs>
          <CartesianGrid
            strokeDasharray="0"
            stroke={COLORS.border}
            horizontal
            vertical={false}
            width={1}
          />
          <XAxis
            dataKey="date"
            tick={{ fontSize: 11, fill: COLORS.textMuted }}
            tickFormatter={formatDateShort}
            interval={Math.ceil(points.length / 5) - 1}
            stroke="transparent"
            tickLine={false}
          />
          <YAxis
            tick={{ fontSize: 11, fill: COLORS.textMuted }}
            tickFormatter={fmt}
            stroke="transparent"
            tickLine={false}
          />
          <Tooltip
            content={({ active, payload }) => (
              <CustomTooltip
                active={active}
                payload={
                  (payload || []) as unknown as Array<{ value: number; payload: ProgressPoint }>
                }
                unit={unit}
                valueFormatter={fmt}
              />
            )}
            cursor={false}
          />
          <Line
            type="monotone"
            dataKey="value"
            stroke={COLORS.primary}
            strokeWidth={2.5}
            dot={props => {
              const { cx, cy, payload } = props as {
                cx: number
                cy: number
                payload: ProgressPoint
              }
              return (
                <g key={`dot-${payload.entryId}`}>
                  <circle
                    cx={cx}
                    cy={cy}
                    r={payload.isPR ? 5.5 : 4.5}
                    fill={payload.isPR ? COLORS.accent : 'var(--color-surface-card)'}
                    stroke={payload.isPR ? COLORS.accent : COLORS.primary}
                    strokeWidth={2.5}
                  />
                  {payload.isPR && (
                    <text
                      x={cx}
                      y={cy - 12}
                      textAnchor="middle"
                      fontSize="10"
                      fontWeight="700"
                      fill={COLORS.accent}
                    >
                      PR
                    </text>
                  )}
                </g>
              )
            }}
            fill="url(#ps-area)"
            isAnimationActive={false}
          />
        </RechartsLineChart>
      </ResponsiveContainer>
    </div>
  )
}

export function VolumeBarChart({ data, height = 160, valueFormatter }: VolumeBarChartProps) {
  const fmt =
    valueFormatter || ((v: number) => (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toString()))

  if (!data.length) {
    return <div className="text-text-muted text-sm py-8 text-center">No volume data yet.</div>
  }

  return (
    <div className="w-full">
      <ResponsiveContainer width="100%" height={height}>
        <RechartsBarChart data={data} margin={{ top: 12, right: 8, bottom: 26, left: 40 }}>
          <CartesianGrid
            strokeDasharray="0"
            stroke={COLORS.border}
            horizontal
            vertical={false}
            width={1}
          />
          <XAxis
            dataKey="date"
            tick={{ fontSize: 11, fill: COLORS.textMuted }}
            tickFormatter={formatDateShort}
            interval={Math.ceil(data.length / 5) - 1}
            stroke="transparent"
            tickLine={false}
          />
          <YAxis
            tick={{ fontSize: 11, fill: COLORS.textMuted }}
            tickFormatter={fmt}
            stroke="transparent"
            tickLine={false}
          />
          <Bar
            dataKey="value"
            fill={COLORS.primary}
            radius={[3, 3, 0, 0]}
            isAnimationActive={false}
          >
            {data.map((_, index) => (
              <Cell key={`cell-${index}`} fill={COLORS.primary} />
            ))}
          </Bar>
        </RechartsBarChart>
      </ResponsiveContainer>
    </div>
  )
}
