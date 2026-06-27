interface SkeletonProps {
  variant?: 'text' | 'title' | 'box' | 'avatar';
  width?: string | number;
  height?: string | number;
  count?: number;
}

export function Skeleton({ variant = 'text', width, height, count = 1 }: SkeletonProps) {
  const style: React.CSSProperties = {};
  if (width !== undefined) style.width = typeof width === 'number' ? `${width}px` : width;
  if (height !== undefined) style.height = typeof height === 'number' ? `${height}px` : height;

  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <span key={i} className={`logixfast-auth-skeleton logixfast-auth-skeleton--${variant}`} style={style} />
      ))}
    </>
  );
}

export function SkeletonCard() {
  return (
    <div className="logixfast-auth-card">
      <div className="logixfast-auth-card-body">
        <Skeleton variant="title" />
        <Skeleton variant="text" count={3} />
      </div>
    </div>
  );
}
