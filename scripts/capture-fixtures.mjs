#!/usr/bin/env node

/**
 * Captures real API responses from an isolated testing-profile stack and writes
 * sanitized JSON fixtures to resources/js/test/mocks/fixtures/.
 *
 * Safety: refuses to run if the target database is not the isolated capture DB.
 * See docs/testing/README.md for usage.
 */

import { spawnSync } from 'node:child_process'
import { writeFileSync, mkdirSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = join(__dirname, '..')
const FIXTURE_DIR = join(ROOT, 'resources/js/test/mocks/fixtures')

const CAPTURE_DB = 'physistrong_dusk'
const COMPOSE_PROJECT = 'physistrong-capture'

const COMPOSE_BASE = [
  'docker', 'compose',
  '-p', COMPOSE_PROJECT,
  '--profile', 'testing',
]

const CAPTURE_ENV = {
  DB_CONNECTION: 'mysql',
  DB_URL: '',
  DB_HOST: 'test-mysql',
  DB_DATABASE: CAPTURE_DB,
  DB_USERNAME: 'physistrong',
  DB_PASSWORD: 'secret',
  CACHE_STORE: 'array',
  QUEUE_CONNECTION: 'sync',
  SESSION_DRIVER: 'file',
  BCRYPT_ROUNDS: '4',
}

const SANITIZE_TIMESTAMP = '2026-01-01T00:00:00.000000Z'
const SANITIZE_EMAIL = 'test@example.com'
const SANITIZE_FIRST = 'Claude'
const SANITIZE_LAST = 'Ai'

const CAPTURE_EMAIL = 'claude-capture@test.local'
const CAPTURE_PASSWORD = 'capture-password-123'

function compose(args, opts = {}) {
  const result = spawnSync(COMPOSE_BASE[0], [...COMPOSE_BASE.slice(1), ...args], {
    cwd: ROOT,
    stdio: opts.capture ? 'pipe' : 'inherit',
    encoding: 'utf-8',
    timeout: opts.timeout ?? 120_000,
  })
  if (result.status !== 0 && !opts.ignoreError) {
    const stderr = result.stderr?.trim() ?? ''
    throw new Error(`compose ${args.join(' ')} failed (exit ${result.status}): ${stderr}`)
  }
  return result.stdout?.trim() ?? ''
}

function envFlags(extraEnv = {}) {
  return Object.entries({ ...CAPTURE_ENV, ...extraEnv })
    .map(([k, v]) => ['-e', `${k}=${v}`])
    .flat()
}

function artisan(args, extraEnv = {}) {
  return compose([
    'run', '--rm', '--no-deps',
    ...envFlags(extraEnv),
    'app', 'php', 'artisan', ...args,
  ], { capture: true })
}

let authCookie = null

async function api(method, path, body) {
  const url = `http://127.0.0.1:${capturePort}/api/v1${path}`
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' }
  if (authCookie) {
    headers.Cookie = authCookie
  }

  const { _expectError, ...payload } = body ?? {}
  const opts = { method, headers }
  if (body) opts.body = JSON.stringify(payload)

  const res = await fetch(url, opts)

  const setCookie = res.headers.getSetCookie?.() ?? []
  for (const c of (Array.isArray(setCookie) ? setCookie : [setCookie])) {
    if (c && c.startsWith('ps_token=')) {
      authCookie = c.split(';')[0]
    }
  }

  const text = await res.text()
  let json
  try {
    json = JSON.parse(text)
  } catch {
    json = null
  }

  if (!res.ok && !_expectError) {
    console.error(`API ${method} ${path} => ${res.status}`, text.slice(0, 500))
  }

  return { status: res.status, json, headers: res.headers }
}

function writeFixture(subpath, data) {
  const dest = join(FIXTURE_DIR, subpath)
  mkdirSync(dirname(dest), { recursive: true })
  writeFileSync(dest, JSON.stringify(data, null, 2) + '\n')
  console.log(`  wrote ${subpath}`)
}

function sanitize(obj) {
  if (obj === null || obj === undefined) return obj
  if (Array.isArray(obj)) return obj.map(sanitize)
  if (typeof obj !== 'object') return obj

  const out = {}
  for (const [key, val] of Object.entries(obj)) {
    if (key === 'created_at' || key === 'updated_at') {
      out[key] = SANITIZE_TIMESTAMP
    } else if (key === 'email' && typeof val === 'string' && val.includes('capture')) {
      out[key] = SANITIZE_EMAIL
    } else if (key === 'first_name' && typeof val === 'string') {
      out[key] = SANITIZE_FIRST
    } else if (key === 'last_name' && typeof val === 'string') {
      out[key] = SANITIZE_LAST
    } else if (key === 'token' && typeof val === 'string') {
      out[key] = 'test-access-token'
    } else {
      out[key] = sanitize(val)
    }
  }
  return out
}

function assertIsolatedDatabase() {
  console.log('\nGuard: verifying database isolation...')
  const output = artisan([
    'tinker', '--execute',
    "echo DB::selectOne('select database() as db')->db;",
  ])

  const lines = output.split('\n').map(l => l.trim()).filter(Boolean)
  const dbName = lines[lines.length - 1]

  if (dbName !== CAPTURE_DB) {
    console.error(
      `\n** REFUSED: capture targets "${dbName}" but must target "${CAPTURE_DB}". **\n`
      + 'The capture script only runs against the isolated testing-profile database.\n'
      + 'Check docker-compose.yml testing profile and CAPTURE_ENV overrides.\n'
    )
    throw new Error('capture target is not the isolated database')
  }

  console.log(`  confirmed database: ${dbName}`)
}

function startStack() {
  console.log('\nStarting testing-profile stack...')
  compose(['up', '-d', 'test-mysql', 'redis'], { timeout: 60_000 })

  console.log('Waiting for test-mysql to accept connections...')
  compose(['run', '--rm', '--no-deps',
    '-e', `DB_HOST=${CAPTURE_ENV.DB_HOST}`,
    '-e', `DB_DATABASE=${CAPTURE_ENV.DB_DATABASE}`,
    '-e', `DB_USERNAME=${CAPTURE_ENV.DB_USERNAME}`,
    '-e', `DB_PASSWORD=${CAPTURE_ENV.DB_PASSWORD}`,
    'app', 'php', '-r',
    `for ($i = 0; $i < 30; $i++) { try { new PDO("mysql:host=${CAPTURE_ENV.DB_HOST};dbname=${CAPTURE_ENV.DB_DATABASE}", "${CAPTURE_ENV.DB_USERNAME}", "${CAPTURE_ENV.DB_PASSWORD}"); echo "ready\\n"; exit(0); } catch (Throwable $e) { sleep(2); } } echo "timeout\\n"; exit(1);`,
  ], { timeout: 120_000 })
}

function resetDatabase() {
  console.log('\nResetting database (migrate:fresh)...')
  artisan(['migrate:fresh', '--force'])

  console.log('Seeding equipment types and exercise library...')
  artisan(['db:seed', '--class=EquipmentTypeSeeder', '--force'])
  artisan(['db:seed', '--class=ExerciseLibrarySeeder', '--force'])

  console.log('Creating Passport personal-access client...')
  artisan(['passport:client', '--personal', '--name=CaptureClient'])
}

let capturePort

function startAppServer() {
  console.log('\nStarting app server with capture env overrides...')

  compose([
    'run', '-d', '--rm', '--no-deps',
    '--name', `${COMPOSE_PROJECT}-app-serve`,
    '-p', '127.0.0.1:0:8000',
    ...envFlags(),
    'app', 'php', 'artisan', 'serve', '--port=8000', '--host=0.0.0.0', '--no-reload',
  ], { timeout: 60_000 })

  const inspectOutput = spawnSync('docker', [
    'port', `${COMPOSE_PROJECT}-app-serve`, '8000',
  ], { encoding: 'utf-8', timeout: 10_000 })

  // docker port outputs one line per binding (IPv4 + IPv6); take the first
  const firstLine = (inspectOutput.stdout ?? '').split('\n')[0]?.trim()
  const match = firstLine?.match(/:(\d+)$/)
  if (!match) {
    throw new Error('Could not determine app server published port.')
  }

  capturePort = match[1]
  console.log(`  app server accessible on port ${capturePort}`)
}

let tornDown = false

function teardown() {
  if (tornDown) return
  tornDown = true
  console.log('\nTearing down capture stack...')
  spawnSync('docker', ['stop', `${COMPOSE_PROJECT}-app-serve`], {
    timeout: 15_000, stdio: 'inherit',
  })
  spawnSync('docker', ['rm', '-f', `${COMPOSE_PROJECT}-app-serve`], {
    timeout: 10_000, stdio: 'inherit',
  })
  compose(['down', '--volumes', '--remove-orphans'], { ignoreError: true, timeout: 60_000 })
}

async function captureAuth() {
  console.log('\n--- Auth fixtures ---')

  const reg = await api('POST', '/register', {
    email: CAPTURE_EMAIL,
    password: CAPTURE_PASSWORD,
    password_confirmation: CAPTURE_PASSWORD,
    measurement_system: 'imperial',
    first_name: 'Capture',
    last_name: 'Test',
  })
  writeFixture('auth/register.json', sanitize(reg.json))

  const login = await api('POST', '/login', {
    email: CAPTURE_EMAIL,
    password: CAPTURE_PASSWORD,
  })
  writeFixture('auth/login.json', sanitize(login.json))
}

async function captureUser() {
  console.log('\n--- User fixtures ---')

  const profile = await api('GET', '/user')
  writeFixture('user/profile.json', sanitize(profile.json))

  const updated = await api('PUT', '/user', {
    first_name: SANITIZE_FIRST,
    last_name: SANITIZE_LAST,
    measurement_system: 'imperial',
    theme: 'system',
  })
  writeFixture('user/profile-updated.json', sanitize(updated.json))

  const valErr = await api('PUT', '/user', {
    measurement_system: 'invalid',
    _expectError: true,
  })
  writeFixture('user/validation-error.json', valErr.json)
}

async function captureEquipment() {
  console.log('\n--- Equipment fixtures ---')

  const created = await api('POST', '/equipment-types', {
    name: 'Capture Test Equipment',
  })
  writeFixture('equipment/created.json', sanitize(created.json))

  const list = await api('GET', '/equipment-types')
  writeFixture('equipment/list.json', sanitize(list.json))

  return created.json.data.id
}

async function captureExercises(equipmentTypeId) {
  console.log('\n--- Exercise fixtures ---')

  const resistance = await api('POST', '/exercises', {
    name: 'Capture Test Resistance',
    type: 'resistance',
    equipment_type_id: equipmentTypeId,
    type_attributes: { bodyweight_base: true, allows_added_weight: true, bilateral: false },
  })
  writeFixture('exercises/created-resistance.json', sanitize(resistance.json))

  const hold = await api('POST', '/exercises', {
    name: 'Capture Test Hold',
    type: 'timed_hold',
    equipment_type_id: equipmentTypeId,
    type_attributes: { target_duration_seconds: 60 },
  })
  writeFixture('exercises/created-timed_hold.json', sanitize(hold.json))

  const distance = await api('POST', '/exercises', {
    name: 'Capture Test Distance',
    type: 'distance',
    equipment_type_id: equipmentTypeId,
    type_attributes: { tracks_elevation: true },
  })
  writeFixture('exercises/created-distance.json', sanitize(distance.json))

  const interval = await api('POST', '/exercises', {
    name: 'Capture Test Interval',
    type: 'interval',
    equipment_type_id: equipmentTypeId,
    type_attributes: { default_work_seconds: 30, default_rest_seconds: 20, default_rounds: 4 },
  })
  writeFixture('exercises/created-interval.json', sanitize(interval.json))

  const showR = await api('GET', `/exercises/${resistance.json.data.id}`)
  writeFixture('exercises/show-resistance.json', sanitize(showR.json))

  const showH = await api('GET', `/exercises/${hold.json.data.id}`)
  writeFixture('exercises/show-timed_hold.json', sanitize(showH.json))

  const showD = await api('GET', `/exercises/${distance.json.data.id}`)
  writeFixture('exercises/show-distance.json', sanitize(showD.json))

  const showI = await api('GET', `/exercises/${interval.json.data.id}`)
  writeFixture('exercises/show-interval.json', sanitize(showI.json))

  const list = await api('GET', '/exercises')
  writeFixture('exercises/list.json', sanitize(list.json))

  const valErr = await api('POST', '/exercises', {
    name: '',
    type: 'resistance',
    _expectError: true,
  })
  writeFixture('exercises/validation-error.json', valErr.json)

  return {
    resistanceId: resistance.json.data.id,
    holdId: hold.json.data.id,
    distanceId: distance.json.data.id,
    intervalId: interval.json.data.id,
  }
}

async function captureWorkouts(exerciseIds) {
  console.log('\n--- Workout fixtures ---')

  const workout = await api('POST', '/workouts', {
    name: 'Capture Test Workout',
    date: '2026-06-15',
  })
  const workoutId = workout.json.data.id

  await api('POST', `/workouts/${workoutId}/entries`, {
    exercise_id: exerciseIds.resistanceId,
    set_order: 1,
    notes: 'First set',
    metrics: {
      load: { target_weight: '135.00', actual_weight: '130.00', bodyweight_only: false },
      reps: { target_reps: 8, actual_reps: 8, to_failure: false, failure_rep: null },
    },
  })

  await api('POST', `/workouts/${workoutId}/entries`, {
    exercise_id: exerciseIds.resistanceId,
    set_order: 2,
    notes: 'Second set',
    metrics: {
      load: { target_weight: '135.00', actual_weight: '135.00', bodyweight_only: false },
      reps: { target_reps: 8, actual_reps: 7, to_failure: false, failure_rep: null },
    },
  })

  await api('POST', `/workouts/${workoutId}/entries`, {
    exercise_id: exerciseIds.holdId,
    set_order: 3,
    metrics: {
      duration: { target_duration_seconds: 60, actual_duration_seconds: 55 },
    },
  })

  await api('POST', `/workouts/${workoutId}/entries`, {
    exercise_id: exerciseIds.distanceId,
    set_order: 4,
    metrics: {
      distance: { target_distance: '5.00', actual_distance: '4.80', lap_count: null, stroke_count: null },
    },
  })

  const created = await api('GET', `/workouts/${workoutId}`)
  writeFixture('workouts/created.json', sanitize(created.json))

  const showWorkout = await api('POST', '/workouts', {
    name: 'Capture Test Show Workout',
    date: '2026-06-01',
  })
  const showId = showWorkout.json.data.id

  await api('POST', `/workouts/${showId}/exercises`, { exercise_id: exerciseIds.resistanceId })
  await api('POST', `/workouts/${showId}/exercises`, { exercise_id: exerciseIds.intervalId })

  const groupRes = await api('POST', `/workouts/${showId}/groups`, {
    name: 'Superset A',
    planned_rounds: 3,
    rest_between_exercises_seconds: 30,
    rest_between_rounds_seconds: 90,
  })
  const groupId = groupRes.json.data.groups[0].id

  await api('POST', `/workouts/${showId}/entries`, {
    exercise_id: exerciseIds.resistanceId,
    set_order: 0,
    entry_group_id: groupId,
    group_round: 1,
    metrics: {
      load: { target_weight: '135.00', actual_weight: '130.00', bodyweight_only: false },
      reps: { target_reps: 8, actual_reps: 8, to_failure: false, failure_rep: null },
    },
  })

  await api('POST', `/workouts/${showId}/entries`, {
    exercise_id: exerciseIds.intervalId,
    set_order: 1,
    metrics: {
      interval_header: {
        programmed_rounds: 4,
        completed_rounds: 4,
        target_work_seconds: 30,
        target_rest_seconds: 15,
        rounds: [
          { round_number: 1, actual_work_seconds: 30, actual_rest_seconds: 15, heart_rate_avg: null, heart_rate_peak: null },
          { round_number: 2, actual_work_seconds: 31, actual_rest_seconds: 14, heart_rate_avg: null, heart_rate_peak: null },
          { round_number: 3, actual_work_seconds: 30, actual_rest_seconds: 15, heart_rate_avg: null, heart_rate_peak: null },
          { round_number: 4, actual_work_seconds: 29, actual_rest_seconds: 16, heart_rate_avg: null, heart_rate_peak: null },
        ],
      },
    },
  })

  // Resistance allows load/reps/intensity; use intensity as the optional dimension
  await api('POST', `/workouts/${showId}/entries`, {
    exercise_id: exerciseIds.resistanceId,
    set_order: 2,
    metrics: {
      load: { target_weight: '185.00', actual_weight: '180.00', bodyweight_only: false },
      reps: { target_reps: 5, actual_reps: 5, to_failure: false, failure_rep: null },
      intensity: { rpe: 8, heart_rate_avg: null, heart_rate_peak: null },
    },
  })

  const show = await api('GET', `/workouts/${showId}`)
  writeFixture('workouts/show.json', sanitize(show.json))

  const copy = await api('POST', `/workouts/${workoutId}/copy`, {
    date: '2026-06-20',
  })
  writeFixture('workouts/copied.json', sanitize(copy.json))

  for (let i = 0; i < 18; i++) {
    const w = await api('POST', '/workouts', {
      name: `Test with Entries ${i + 1}`,
      date: '2026-06-16',
    })
    await api('POST', `/workouts/${w.json.data.id}/entries`, {
      exercise_id: exerciseIds.resistanceId,
      set_order: 0,
      metrics: {
        load: { target_weight: '100.00', actual_weight: '95.00', bodyweight_only: false },
        reps: { target_reps: 10, actual_reps: 10, to_failure: false },
      },
    })
  }

  const page1 = await api('GET', '/workouts?page=1')
  writeFixture('workouts/list-page-1.json', sanitize(page1.json))

  const page2 = await api('GET', '/workouts?page=2')
  writeFixture('workouts/list-page-2.json', sanitize(page2.json))

  return { workoutId, showId, exerciseIds }
}

async function captureTemplates() {
  console.log('\n--- Template fixtures ---')

  const t1 = await api('POST', '/templates', {
    name: 'Capture Test Template',
  })
  writeFixture('templates/created.json', sanitize(t1.json))

  const t2 = await api('POST', '/templates', {
    name: 'Capture Test Template B',
  })

  const list = await api('GET', '/templates')
  writeFixture('templates/list.json', sanitize(list.json))

  const show = await api('GET', `/templates/${t1.json.data.id}`)
  writeFixture('templates/show.json', sanitize(show.json))

  const cloned = await api('POST', `/templates/${t1.json.data.id}/clone`, {
    date: '2026-06-25',
  })
  writeFixture('templates/cloned.json', sanitize(cloned.json))
}

async function captureProgress(resistanceId, distanceId) {
  console.log('\n--- Progress fixtures ---')

  const progressData = [
    { date: '2026-04-01', weight: 95, reps: 10 },
    { date: '2026-04-15', weight: 100, reps: 10 },
    { date: '2026-05-01', weight: 105, reps: 8 },
    { date: '2026-05-15', weight: 102, reps: 8 },
  ]

  for (const p of progressData) {
    const w = await api('POST', '/workouts', {
      name: 'Progress Workout',
      date: p.date,
    })
    await api('POST', `/workouts/${w.json.data.id}/entries`, {
      exercise_id: resistanceId,
      set_order: 0,
      metrics: {
        load: { target_weight: String(p.weight), actual_weight: String(p.weight), bodyweight_only: false },
        reps: { target_reps: p.reps, actual_reps: p.reps, to_failure: false },
      },
    })
  }

  const progress = await api('GET', `/exercises/${resistanceId}/progress`)
  writeFixture('progress/progress-resistance.json', sanitize(progress.json))

  const records = await api('GET', `/exercises/${resistanceId}/records`)
  writeFixture('progress/records-resistance.json', sanitize(records.json))

  // Time-only cardio gives the distance exercise a second populated series.
  const cardioData = [
    { date: '2026-04-10', seconds: 1500 },
    { date: '2026-05-10', seconds: 2100 },
    { date: '2026-06-10', seconds: 2400 },
  ]

  for (const c of cardioData) {
    const w = await api('POST', '/workouts', {
      name: 'Cardio Session',
      date: c.date,
    })
    await api('POST', `/workouts/${w.json.data.id}/entries`, {
      exercise_id: distanceId,
      set_order: 0,
      metrics: {
        duration: { target_duration_seconds: null, actual_duration_seconds: c.seconds },
      },
    })
  }

  const distanceProgress = await api('GET', `/exercises/${distanceId}/progress`)
  writeFixture('progress/progress-distance.json', sanitize(distanceProgress.json))

  const distanceRecords = await api('GET', `/exercises/${distanceId}/records`)
  writeFixture('progress/records-distance.json', sanitize(distanceRecords.json))
}

async function main() {
  console.log('=== Physistrong Fixture Capture ===')
  console.log(`Capture DB: ${CAPTURE_DB}`)

  try {
    startStack()
    assertIsolatedDatabase()
    resetDatabase()
    startAppServer()

    await new Promise(r => setTimeout(r, 3000))

    await captureAuth()
    await captureUser()
    const equipmentTypeId = await captureEquipment()
    const exerciseIds = await captureExercises(equipmentTypeId)
    await captureWorkouts(exerciseIds)
    await captureTemplates()
    await captureProgress(exerciseIds.resistanceId, exerciseIds.distanceId)

    console.log('\nCapture complete. Review fixtures in resources/js/test/mocks/fixtures/')
  } finally {
    teardown()
  }
}

main().catch(err => {
  console.error('\nCapture failed:', err.message)
  teardown()
  process.exit(1)
})
