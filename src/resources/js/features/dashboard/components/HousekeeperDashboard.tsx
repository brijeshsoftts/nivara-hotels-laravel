import { formatDate } from "date-fns/format";
import { CalendarDays, CheckCircle2, LoaderCircle, Play } from "lucide-react";
import { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import { toast } from "sonner";

import {
  HousekeepingBadge,
  OccupancyBadge,
  TaskStatusBadge,
  TaskTypeBadge,
} from "@/components/common/EnumBadges";
import { Button } from "@/components/ui/button";
import { HouseKeeperRoomDetails } from "@/features/rooms/components/HouseKeeperRoomDetails";
import { useAuth } from "@/hooks/useAuth";

import { DashboardStats } from "./DashboardStats";

function QueueHeader({
  title,
  description,
  id,
}: {
  title: string;
  description: string;
  id: string;
}) {
  return (
    <div className="space-y-1">
      <h2 id={id} className="text-base font-semibold text-foreground">
        {title}
      </h2>
      <p className="text-xs text-muted-foreground">{description}</p>
    </div>
  );
}

function TaskQueueRow({ task }: { task: any }) {
  const [isUpdating, setIsUpdating] = useState(false);
  const nextStatus = task.status === "PENDING" ? "IN_PROGRESS" : "COMPLETED";
  const actionLabel =
    task.status === "PENDING" ? "Start task" : "Complete task";
  const taskDate = task.due;
  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);
  const isOverdue = taskDate ? new Date(taskDate) < startOfToday : false;

  const handleStatusUpdate = () => {
    setIsUpdating(true);
    router.patch(
      `/dashboard/tasks/${task.taskId}/status`,
      { status: nextStatus },
      {
        onSuccess: () => {
          toast.success("Task updated successfully");
        },
        onError: () => {
          toast.error("Failed to update task");
        },
        onFinish: () => setIsUpdating(false),
      },
    );
  };

  return (
    <li className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <p className="text-sm font-medium">
            Room {task.roomNumber}
          </p>
          <TaskTypeBadge value={task.taskType} />
          <TaskStatusBadge value={task.status} />
        </div>
        <p
          className={`mt-1 flex items-center gap-1.5 text-xs ${isOverdue ? "text-destructive" : "text-muted-foreground"}`}
        >
          <CalendarDays className="size-3.5" />
          {isOverdue ? "Overdue · " : "Due "}
          {taskDate ? formatDate(taskDate, "dd MMM, yyyy") : "Today"}
        </p>
      </div>

      <div className="flex flex-wrap items-center gap-2 sm:justify-end">
        <Button
          type="button"
          size="sm"
          onClick={handleStatusUpdate}
          disabled={isUpdating}
        >
          {isUpdating ? (
            <LoaderCircle className="animate-spin" />
          ) : task.status === "PENDING" ? (
            <Play />
          ) : (
            <CheckCircle2 />
          )}
          {isUpdating ? "Updating..." : actionLabel}
        </Button>
        {task.roomId ? (
          <HouseKeeperRoomDetails
            roomId={task.roomId}
            buttonLabel="View room"
          />
        ) : null}
      </div>
    </li>
  );
}

export function HousekeeperDashboard({
  tasks: propTasks,
  cleaningRooms: propCleaningRooms,
}: {
  tasks?: any[];
  cleaningRooms?: any[];
  roomStatus?: any;
} = {}) {
  const { user } = useAuth();
  const page = usePage<any>();
  const tasks = propTasks ?? page.props?.tasks ?? [];
  const cleaningRooms = propCleaningRooms ?? page.props?.cleaningRooms ?? [];

  return (
    <div className="space-y-6">
      <DashboardStats />
      <div className="grid items-start gap-6 xl:grid-cols-2">
        <section
          className="min-w-0 space-y-3"
          aria-labelledby="my-tasks-title"
        >
          <QueueHeader
            id="my-tasks-title"
            title="My tasks"
            description="Open tasks ordered by due date and status."
          />
          {tasks.length === 0 ? (
            <div className="rounded-lg border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
              No open tasks are assigned to you.
            </div>
          ) : (
            <ul className="divide-y rounded-lg border bg-card">
              {tasks.map((task: any) => (
                <TaskQueueRow key={task.taskId} task={task} />
              ))}
            </ul>
          )}
        </section>

        {user?.category === "HOUSEKEEPER" && (
          <section
            className="min-w-0 space-y-3"
            aria-labelledby="cleaning-rooms-title"
          >
            <QueueHeader
              id="cleaning-rooms-title"
              title="Rooms requiring cleaning"
              description="Dirty rooms within your assigned task scope."
            />
            {cleaningRooms.length === 0 ? (
              <div className="rounded-lg border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                No assigned rooms are waiting for cleaning.
              </div>
            ) : (
              <ul className="divide-y rounded-lg border bg-card">
                {cleaningRooms.map((room: any) => (
                  <li
                    key={room.id}
                    className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center"
                  >
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium">
                        Room {room.roomNumber}
                      </p>
                      <p className="mt-1 truncate text-xs text-muted-foreground">
                        {room.roomType?.name} · Floor {room.floor}
                      </p>
                      <div className="mt-2 flex flex-wrap gap-2">
                        <HousekeepingBadge value={room.housekeepingStatus} />
                        <OccupancyBadge value={room.occupancyStatus} />
                      </div>
                    </div>
                    <HouseKeeperRoomDetails
                      roomId={room.id}
                      buttonLabel="View room"
                    />
                  </li>
                ))}
              </ul>
            )}
          </section>
        )}
      </div>
    </div>
  );
}
