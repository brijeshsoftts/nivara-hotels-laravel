import type { TaskStatus, TaskType } from "@/types/enum.types";

export type TaskItem = {
  id: string;
  room: {
    id: string;
    roomNumber: string;
  };
  taskType: TaskType;
  status: TaskStatus;
  scheduledDate: string;
  startedAt: string | null;
  completedAt: string | null;
  assignee: {
    id: string;
    name: string;
  };
};

export type TaskDetails = TaskItem & {
  createdAt: string;
  updatedAt: string;
  room: {
    id: string;
    roomNumber: string;
    name: string;
    floor: number;
    description: string;
    occupancyStatus: string;
    housekeepingStatus: string;
    roomType: {
      id: string;
      name: string;
    };
  };
  assignee: {
    id: string;
    name: string;
    email: string;
    phone: string;
  };
  assignment: {
    id: string;
    isActive: boolean;
    createdAt: string;
    housekeeper: {
      id: string;
      name: string;
    };
    assignedBy: {
      id: string;
      name: string;
    };
  };
};

export type HousekeeperLookupItem = {
  id: string;
  name: string;
  phone: string;
};

export type RoomLookupItem = {
  id: string;
  name: string | null;
  floor: number;
  roomNumber: string;
};
