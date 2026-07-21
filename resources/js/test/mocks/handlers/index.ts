import { authHandlers } from './auth'
import { userHandlers } from './user'
import { equipmentHandlers } from './equipment'
import { exerciseHandlers } from './exercises'
import { workoutHandlers } from './workouts'
import { templateHandlers } from './templates'

export const handlers = [
  ...authHandlers,
  ...userHandlers,
  ...equipmentHandlers,
  ...exerciseHandlers,
  ...workoutHandlers,
  ...templateHandlers,
]
